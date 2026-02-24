<?php

namespace App\Http\Controllers\Back\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Services\MailchimpNewsletterService;
use Illuminate\Http\Request;

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
        $pendingSyncCount = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->count();

        return view('back.marketing.newsletter.index', compact('subscribers', 'pendingSyncCount'));
    }

    public function syncMailchimp(Request $request, MailchimpNewsletterService $service)
    {
        $subscribers = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->orderBy('id')
            ->get();

        if ($subscribers->isEmpty()) {
            return back()->with('status', 'Nema novih newsletter prijava za Mailchimp import.');
        }

        $ok = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            $result = $service->syncSubscriber($subscriber);

            if ($result['ok']) {
                $subscriber->update([
                    'mailchimp_synced_at' => now(),
                    'mailchimp_last_error' => null,
                ]);
                $ok++;
            } else {
                $subscriber->update([
                    'mailchimp_last_error' => $result['error'],
                ]);
                $failed++;
            }
        }

        return back()->with(
            'status',
            'Mailchimp import završen. Uspješno: ' . $ok . ', neuspješno: ' . $failed . '.'
        );
    }
}
