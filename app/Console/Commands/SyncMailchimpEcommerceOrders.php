<?php

namespace App\Console\Commands;

use App\Services\MailchimpOrderSynchronizer;
use Illuminate\Console\Command;

class SyncMailchimpEcommerceOrders extends Command
{
    protected $signature = 'mailchimp:sync-ecommerce-orders
        {--limit=5 : Maximum number of attributed orders per run}
        {--max-seconds=50 : Stop before the next scheduler minute}';

    protected $description = 'Sigurno sinkronizira završene i Mailchimp kampanji pripisane narudžbe.';

    public function handle(MailchimpOrderSynchronizer $synchronizer): int
    {
        if (! $synchronizer->isAvailable()) {
            $this->warn('Mailchimp e-commerce nije konfiguriran ili migracija još nije pokrenuta.');

            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 25));
        $maxSeconds = max(5, min((int) $this->option('max-seconds'), 55));
        $startedAt = microtime(true);
        $orders = $synchronizer->pendingOrders($limit);
        $synced = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if ((microtime(true) - $startedAt) >= $maxSeconds) {
                break;
            }

            $result = $synchronizer->syncOrderId((int) $order->id);

            if ($result['skipped']) {
                $skipped++;
            } elseif ($result['ok']) {
                $synced++;
            } else {
                $failed++;
            }

            if ($result['stop']) {
                break;
            }
        }

        $this->info(sprintf(
            'Mailchimp e-commerce sync završen. Sinkronizirano: %d, neuspjelo: %d, preskočeno: %d.',
            $synced,
            $failed,
            $skipped
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
