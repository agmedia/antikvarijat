<?php

namespace App\Console\Commands;

use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Models\Back\Orders\Order;
use App\Models\Cart;
use App\Services\MailchimpNewsletterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAbandonedCartsToMailchimp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailchimp:sync-abandoned-carts {--minutes=60} {--limit=200}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tag abandoned carts in Mailchimp for eligible newsletter contacts.';

    /**
     * Execute the console command.
     *
     * @param MailchimpNewsletterService $mailchimp
     *
     * @return int
     */
    public function handle(MailchimpNewsletterService $mailchimp): int
    {
        $minutes = max((int) $this->option('minutes'), 15);
        $limit = max((int) $this->option('limit'), 1);
        $cutoff = now()->subMinutes($minutes);

        $abandonedTag = (string) config('services.mailchimp.abandoned_cart_tag', 'abandoned_cart');
        $customerTag = (string) config('services.mailchimp.customer_tag', 'customer');
        $paidStatuses = [3, 4];

        $carts = Cart::query()
            ->with('user:id,email')
            ->whereNotNull('user_id')
            ->where('updated_at', '<=', $cutoff)
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $processed = 0;
        $tagged = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($carts as $cart) {
            $processed++;

            $email = strtolower(trim((string) optional($cart->user)->email));
            if ($email === '') {
                $skipped++;
                continue;
            }

            if (! $this->cartHasItems($cart->cart_data)) {
                $skipped++;
                continue;
            }

            $hasConsent = NewsletterSubscriber::query()
                ->where('email', $email)
                ->where('status', 1)
                ->where('gdpr', 1)
                ->exists();

            if (! $hasConsent) {
                $skipped++;
                continue;
            }

            $hasPaidOrderAfterCart = Order::query()
                ->where('payment_email', $email)
                ->whereIn('order_status_id', $paidStatuses)
                ->where('updated_at', '>=', $cart->updated_at)
                ->exists();

            if ($hasPaidOrderAfterCart) {
                $skipped++;
                continue;
            }

            $result = $mailchimp->updateMemberTags($email, [$abandonedTag], [$customerTag]);

            if ($result['ok']) {
                $tagged++;
            } else {
                $failed++;
                Log::warning('Mailchimp abandoned cart tagging failed', [
                    'cart_id' => $cart->id,
                    'email' => $email,
                    'error' => $result['error'],
                ]);
            }
        }

        $this->info(
            'Abandoned cart sync gotov. Obradjeno: ' . $processed
            . ', tagirano: ' . $tagged
            . ', preskoceno: ' . $skipped
            . ', greske: ' . $failed
            . '.'
        );

        return self::SUCCESS;
    }

    /**
     * @param mixed $cartData
     *
     * @return bool
     */
    private function cartHasItems($cartData): bool
    {
        if (is_string($cartData)) {
            $decoded = json_decode($cartData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $cartData = $decoded;
            }
        }

        if (! is_array($cartData)) {
            return false;
        }

        $items = $cartData['items'] ?? [];

        if (! is_array($items)) {
            return false;
        }

        return count($items) > 0;
    }
}
