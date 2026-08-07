<?php

namespace App\Http\Controllers\Front;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Models\ContractWithdrawal;
use App\Services\ContractWithdrawalNotificationService;
use App\Services\ContractWithdrawalSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContractWithdrawalController extends Controller
{
    private const DRAFT_SESSION_PREFIX = 'contract_withdrawal_drafts.';

    private $notifications;
    private $settings;

    public function __construct(
        ContractWithdrawalNotificationService $notifications,
        ContractWithdrawalSettingsService $settings
    ) {
        $this->notifications = $notifications;
        $this->settings = $settings;
    }

    public function create(Request $request)
    {
        $locale = app()->getLocale();
        $settings = $this->settings->forLocale($locale);

        return view('front.contract-withdrawals.create', [
            'prefill' => $this->prefill($request),
            'withdrawalSettings' => $settings,
            'returnCostText' => $this->settings->returnCostText($settings, $locale),
            'captchaEnabled' => $this->captchaEnabled(),
        ]);
    }

    public function review(Request $request)
    {
        $validated = $this->validateSubmission($request);
        $this->verifyCaptcha($request, $validated);

        $data = $this->normalize($validated);
        $token = (string) Str::uuid();

        $request->session()->put(self::DRAFT_SESSION_PREFIX.$token, [
            'data' => $data,
            'locale' => app()->getLocale(),
            'user_id' => optional($request->user())->id,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return view('front.contract-withdrawals.review', [
            'draftToken' => $token,
            'withdrawal' => $data,
            'declaration' => $this->declaration($data['order_number'], app()->getLocale()),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'draft_token' => ['required', 'uuid'],
        ]);

        $token = (string) $validated['draft_token'];
        $draftKey = self::DRAFT_SESSION_PREFIX.$token;
        $draft = $request->session()->get($draftKey);

        if (! is_array($draft) || (int) ($draft['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget($draftKey);

            return redirect()
                ->to(LocaleHelper::route('contract-withdrawal.create'))
                ->withErrors(['draft' => __('contract_withdrawal.expired')]);
        }

        $data = (array) ($draft['data'] ?? []);
        $locale = (string) ($draft['locale'] ?? LocaleHelper::DEFAULT_LOCALE);
        $declaration = $this->declaration((string) ($data['order_number'] ?? ''), $locale);
        $submittedAt = now();
        $snapshot = [
            'version' => '2026-07-29',
            'submitted_at' => $submittedAt->toIso8601String(),
            'confirmation_channel' => 'email',
            'data' => $data,
            'declaration' => $declaration,
        ];
        $submissionKey = hash('sha256', $token);

        $withdrawal = DB::transaction(function () use (
            $request,
            $draft,
            $data,
            $declaration,
            $submittedAt,
            $snapshot,
            $submissionKey
        ) {
            $existing = ContractWithdrawal::query()
                ->where('submission_key', $submissionKey)
                ->first();

            if ($existing) {
                return $existing;
            }

            $order = $this->resolveOrder(
                (string) $data['order_number'],
                (string) $data['email'],
                isset($draft['user_id']) ? (int) $draft['user_id'] : null
            );

            return ContractWithdrawal::query()->create([
                'reference' => $this->newReference(),
                'submission_key' => $submissionKey,
                'user_id' => isset($draft['user_id']) ? (int) $draft['user_id'] : null,
                'order_id' => optional($order)->id,
                'order_number' => $data['order_number'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?: null,
                'address_line' => $data['address_line'],
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
                'country_code' => $data['country_code'],
                'contract_date' => $data['contract_date'] ?: null,
                'received_date' => $data['received_date'] ?: null,
                'items' => $data['items'],
                'note' => $data['note'] ?: null,
                'declaration' => $declaration,
                'request_snapshot' => $snapshot,
                'snapshot_hash' => ContractWithdrawal::snapshotHash($snapshot),
                'status' => ContractWithdrawal::STATUS_RECEIVED,
                'locale' => (string) ($draft['locale'] ?? 'hr'),
                'submitted_at' => $submittedAt,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
            ]);
        });

        $request->session()->forget($draftKey);
        $this->notifications->send($withdrawal);
        $withdrawal->refresh();

        $redirect = redirect()
            ->to(LocaleHelper::route('contract-withdrawal.create', [], true, $locale))
            ->with(
                'success',
                trans('contract_withdrawal.success', [
                    'reference' => $withdrawal->reference,
                ], $locale)
            )
            ->with('withdrawal_reference', $withdrawal->reference);

        if (! $withdrawal->consumer_notified_at) {
            $redirect->with(
                'warning',
                trans('contract_withdrawal.email_warning', [], $locale)
            );
        }

        return $redirect;
    }

    private function validateSubmission(Request $request): array
    {
        $captchaEnabled = $this->captchaEnabled();
        $receivedDateRules = ['nullable', 'date', 'before_or_equal:today'];

        if ($request->filled('contract_date')) {
            $receivedDateRules[] = 'after_or_equal:contract_date';
        }

        return $request->validate(
            [
                'full_name' => ['required', 'string', 'min:2', 'max:191'],
                'email' => ['required', 'email', 'max:191'],
                'phone' => ['nullable', 'string', 'max:80'],
                'address_line' => ['required', 'string', 'min:3', 'max:255'],
                'postal_code' => ['required', 'string', 'max:32'],
                'city' => ['required', 'string', 'max:120'],
                'country_code' => ['required', 'string', 'size:2'],
                'order_number' => ['required', 'string', 'max:80'],
                'contract_date' => ['nullable', 'date', 'before_or_equal:today'],
                'received_date' => $receivedDateRules,
                'items' => ['required', 'string', 'min:2', 'max:5000'],
                'note' => ['nullable', 'string', 'max:5000'],
                'recaptcha' => [$captchaEnabled ? 'required' : 'nullable', 'string', 'max:4096'],
            ],
            [
                'required' => __('contract_withdrawal.validation.required'),
                'email' => __('contract_withdrawal.validation.email'),
                'min.string' => __('contract_withdrawal.validation.min_string'),
                'max.string' => __('contract_withdrawal.validation.max_string'),
                'size' => __('contract_withdrawal.validation.size'),
                'date' => __('contract_withdrawal.validation.date'),
                'before_or_equal' => __('contract_withdrawal.validation.before_or_equal'),
                'after_or_equal' => __('contract_withdrawal.validation.after_or_equal'),
            ],
            trans('contract_withdrawal.attributes')
        );
    }

    private function normalize(array $validated): array
    {
        return [
            'full_name' => trim((string) $validated['full_name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'phone' => trim((string) ($validated['phone'] ?? '')),
            'address_line' => trim((string) $validated['address_line']),
            'postal_code' => trim((string) $validated['postal_code']),
            'city' => trim((string) $validated['city']),
            'country_code' => strtoupper(trim((string) $validated['country_code'])),
            'order_number' => trim((string) $validated['order_number']),
            'contract_date' => trim((string) ($validated['contract_date'] ?? '')),
            'received_date' => trim((string) ($validated['received_date'] ?? '')),
            'items' => trim((string) $validated['items']),
            'note' => trim((string) ($validated['note'] ?? '')),
        ];
    }

    private function verifyCaptcha(Request $request, array $validated): void
    {
        if (! $this->captchaEnabled()) {
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post((string) config('services.recaptcha.verify_url'), [
                    'secret' => (string) config('services.recaptcha.secret'),
                    'response' => (string) ($validated['recaptcha'] ?? ''),
                    'remoteip' => (string) $request->ip(),
                ]);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'recaptcha' => __('contract_withdrawal.captcha_failed'),
            ]);
        }

        $result = $response->ok() ? (array) $response->json() : [];
        $action = (string) ($result['action'] ?? '');

        if (
            ! (bool) ($result['success'] ?? false)
            || (float) ($result['score'] ?? 0) < 0.3
            || ($action !== '' && $action !== 'contract_withdrawal')
        ) {
            throw ValidationException::withMessages([
                'recaptcha' => __('contract_withdrawal.captcha_failed'),
            ]);
        }
    }

    private function captchaEnabled(): bool
    {
        if (app()->environment('local') && config('services.recaptcha.bypass_local', true)) {
            return false;
        }

        return trim((string) config('services.recaptcha.sitekey')) !== ''
            && trim((string) config('services.recaptcha.secret')) !== '';
    }

    private function declaration(string $orderNumber, ?string $locale = null): string
    {
        return (string) trans('contract_withdrawal.declaration', [
            'order_number' => $orderNumber,
        ], $locale ?: app()->getLocale());
    }

    private function newReference(): string
    {
        do {
            $reference = 'JR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (ContractWithdrawal::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function resolveOrder(string $orderNumber, string $email, ?int $userId): ?Order
    {
        $orderId = ltrim(trim($orderNumber), '#');

        if (! ctype_digit($orderId)) {
            return null;
        }

        return Order::query()
            ->whereKey((int) $orderId)
            ->where(function ($query) use ($email, $userId): void {
                $query
                    ->where('payment_email', $email)
                    ->orWhere('shipping_email', $email);

                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->first();
    }

    private function prefill(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        $user->loadMissing('details');
        $details = $user->details;

        return array_filter([
            'full_name' => trim(implode(' ', array_filter([
                optional($details)->fname,
                optional($details)->lname,
            ]))) ?: $user->name,
            'email' => $user->email,
            'phone' => optional($details)->phone,
            'address_line' => optional($details)->address,
            'postal_code' => optional($details)->zip,
            'city' => optional($details)->city,
            'country_code' => 'HR',
        ], static function ($value): bool {
            return $value !== null && $value !== '';
        });
    }
}
