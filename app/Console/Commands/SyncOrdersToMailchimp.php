<?php

namespace App\Console\Commands;

use App\Models\Back\Orders\Order;
use App\Services\MailchimpEcommerceService;
use App\Services\MailchimpNewsletterService;
use Illuminate\Console\Command;
use Throwable;

class SyncOrdersToMailchimp extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mailchimp:sync-orders {--days=3650} {--chunk=100}';

    /**
     * @var string
     */
    protected $description = 'Backfill paid orders to the Mailchimp e-commerce store.';

    public function handle(MailchimpEcommerceService $mailchimp, MailchimpNewsletterService $newsletter): int
    {
        if (! $mailchimp->isConfigured()) {
            $this->error('Mailchimp e-commerce nije konfiguriran.');

            return self::FAILURE;
        }

        $days = max((int) $this->option('days'), 0);
        $chunk = max((int) $this->option('chunk'), 1);

        $query = Order::query()
            ->whereIn('order_status_id', [3, 4])
            ->whereNotNull('payment_email');

        if ($days > 0) {
            $query->where('updated_at', '>=', now()->subDays($days));
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nema plaćenih narudžbi za Mailchimp order sync.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $customerFailures = 0;
        $errorSamples = [];

        $lastId = null;

        do {
            $orders = (clone $query)
                ->when($lastId !== null, function ($orderQuery) use ($lastId) {
                    $orderQuery->where('id', '<', $lastId);
                })
                ->orderByDesc('id')
                ->limit($chunk)
                ->get();

            if ($orders->isEmpty()) {
                break;
            }

            foreach ($orders as $order) {
                try {
                    $result = $mailchimp->syncOrder($order);
                } catch (Throwable $e) {
                    $result = [
                        'ok' => false,
                        'error' => $e->getMessage(),
                    ];
                }

                if (! $result['ok']) {
                    if (count($errorSamples) < 3 && ! empty($result['error'])) {
                        $errorSamples[] = 'Order ' . $order->id . ': ' . $result['error'];
                    }

                    $failed++;

                    continue;
                }

                try {
                    $customerResult = $newsletter->syncCustomerFromOrder($order);
                } catch (Throwable $e) {
                    $customerResult = [
                        'ok' => false,
                        'error' => $e->getMessage(),
                    ];
                }

                if (! $customerResult['ok']) {
                    $customerFailures++;
                    if (count($errorSamples) < 3 && ! empty($customerResult['error'])) {
                        $errorSamples[] = 'Order ' . $order->id . ': ' . $customerResult['error'];
                    }

                    continue;
                }

                $ok++;
            }

            $lastId = (int) $orders->last()->id;
        } while ($orders->count() === $chunk);

        $message = 'Mailchimp order sync gotov. Ukupno: ' . $total
            . ', uspješno: ' . $ok
            . ', greške: ' . $failed
            . ', customer sync greške: ' . $customerFailures . '.';

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        $this->info($message);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
