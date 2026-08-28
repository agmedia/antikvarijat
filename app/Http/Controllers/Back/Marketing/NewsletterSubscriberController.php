<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Services\MailchimpNewsletterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query()
            ->with(['user.details'])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', '%' . $search . '%')
                    ->orWhere('order_id', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhereHas('details', function ($dq) use ($search) {
                                $dq->where('fname', 'like', '%' . $search . '%')
                                    ->orWhere('lname', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $subscribers = $query->paginate(30);
        $pendingMailchimpCount = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->count();

        return view('back.marketing.newsletter.index', compact('subscribers', 'pendingMailchimpCount'));
    }

    public function syncMailchimp(Request $request, MailchimpNewsletterService $mailchimp)
    {
        $user = $request->user();
        abort_unless(
            $user
                && $user->isAdministrator()
                && (bool) optional($user->details)->status,
            403
        );

        @set_time_limit(60);

        $lastId = max((int) $request->input('last_id', 0), 0);
        $batchSize = min(max((int) $request->input('batch', 20), 1), 20);
        $connection = $mailchimp->connectionStatus($lastId === 0);

        if (! $connection['ok']) {
            return $this->mailchimpSyncResponse($request, [
                'ok' => false,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'total' => 0,
                'remaining' => 0,
                'pending' => $this->pendingMailchimpCount(),
                'finished' => true,
                'message' => (string) $connection['error'],
            ]);
        }

        $pendingQuery = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at');

        $total = (clone $pendingQuery)->count();
        $subscribers = (clone $pendingQuery)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        if ($subscribers->isEmpty()) {
            return $this->mailchimpSyncResponse($request, [
                'ok' => true,
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'last_id' => $lastId,
                'total' => $total,
                'remaining' => 0,
                'pending' => $this->pendingMailchimpCount(),
                'finished' => true,
                'message' => $total === 0
                    ? 'Sve aktivne newsletter prijave s GDPR privolom već su sinkronizirane.'
                    : 'Ova Mailchimp sinkronizacija je završena.',
            ]);
        }

        $synced = 0;
        $failed = 0;
        $processed = 0;
        $errors = [];
        $lastProcessedId = $lastId;
        $systemError = null;

        foreach ($subscribers as $subscriber) {
            try {
                $result = $mailchimp->syncSubscriber($subscriber);
            } catch (Throwable $e) {
                Log::warning('Unexpected Mailchimp newsletter sync failure', [
                    'subscriber_id' => $subscriber->id,
                    'exception' => get_class($e),
                ]);

                $result = [
                    'ok' => false,
                    'error' => 'Neočekivana greška pri komunikaciji s Mailchimpom.',
                    'stop' => true,
                ];
            }

            if ($result['ok']) {
                $subscriber->forceFill([
                    'mailchimp_synced_at' => now(),
                    'mailchimp_last_error' => null,
                ])->save();
                $synced++;
                $processed++;
                $lastProcessedId = (int) $subscriber->id;

                continue;
            }

            $error = Str::limit(
                trim((string) ($result['error'] ?? 'Nepoznata Mailchimp greška.')),
                1000,
                '…'
            );

            if (! empty($result['stop'])) {
                $systemError = $error;
                break;
            }

            $subscriber->forceFill(['mailchimp_last_error' => $error])->save();
            $failed++;
            $processed++;
            $lastProcessedId = (int) $subscriber->id;

            if (count($errors) < 3) {
                $errors[] = 'ID ' . $subscriber->id . ': ' . $error;
            }
        }

        $remaining = (clone $pendingQuery)
            ->where('id', '>', $lastProcessedId)
            ->count();

        if ($systemError !== null) {
            return $this->mailchimpSyncResponse($request, [
                'ok' => false,
                'processed' => $processed,
                'synced' => $synced,
                'failed' => $failed,
                'last_id' => $lastProcessedId,
                'total' => $total,
                'remaining' => $remaining,
                'pending' => $this->pendingMailchimpCount(),
                'finished' => false,
                'message' => 'Sinkronizacija je privremeno zaustavljena: ' . $systemError,
            ]);
        }

        $finished = $remaining === 0;

        $message = 'Obrađeno: ' . $processed
            . ', uspješno: ' . $synced
            . ', greške: ' . $failed . '.';

        if (! $finished) {
            $message .= ' Preostalo u ovom prolazu: ' . $remaining . '.';
        }

        if ($errors !== []) {
            $message .= ' Primjeri grešaka: ' . implode(' | ', array_unique($errors));
        }

        return $this->mailchimpSyncResponse($request, [
            'ok' => true,
            'processed' => $processed,
            'synced' => $synced,
            'failed' => $failed,
            'last_id' => $lastProcessedId,
            'total' => $total,
            'remaining' => $remaining,
            'pending' => $this->pendingMailchimpCount(),
            'finished' => $finished,
            'message' => $message,
        ]);
    }

    public function clearCaches()
    {
        @set_time_limit(0);

        Artisan::call('optimize:clear');
        Artisan::call('cache:clear');

        return back()->with('status', trim(Artisan::output()) ?: 'Laravel cache je očišćen.');
    }

    private function pendingMailchimpCount(): int
    {
        return NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->count();
    }

    private function mailchimpSyncResponse(Request $request, array $result)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        if ((int) $result['failed'] > 0) {
            return back()->with('warning', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
