<?php

namespace App\Console\Commands;

use App\Models\AbandonedCartReminder;
use App\Services\AbandonedCartReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'orders:send-abandoned-cart-reminders
                            {--dry-run : Prikaži što bi se poslalo bez slanja}
                            {--limit= : Najveći broj poruka u jednom pokretanju}';

    protected $description = 'Šalje dva podsjetnika kupcima s nedovršenom košaricom';

    public function handle(AbandonedCartReminderService $service): int
    {
        if (! config('abandoned_cart.enabled', true)) {
            $this->warn('Podsjetnici za nedovršene košarice su isključeni.');

            return 0;
        }

        if (! $service->isAvailable()) {
            $this->error('Nedostaju stupci ili tablica za evidenciju podsjetnika. Primijenite SQL 034.');

            return 1;
        }

        $limit = $this->option('limit') !== null
            ? max(1, (int) $this->option('limit'))
            : max(1, (int) config('abandoned_cart.batch_size', 25));
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $sent = 0;
        $failed = 0;

        for ($sequence = 1; $sequence <= (int) config('abandoned_cart.max_reminders', 2); $sequence++) {
            $remaining = max(0, $limit - $sent);
            if ($remaining === 0) {
                break;
            }

            foreach ($service->candidatesForSequence($sequence, $remaining) as $order) {
                if ($dryRun) {
                    $rows[] = [
                        $order->id,
                        $sequence,
                        $service->scheduledFor($order, $sequence)->format('d.m.Y. H:i'),
                        $order->resolvedLocale(),
                        $this->maskEmail((string) $order->payment_email),
                    ];
                    $sent++;
                    continue;
                }

                try {
                    $service->send($order, $sequence, AbandonedCartReminder::SOURCE_AUTOMATIC);
                    $sent++;
                    $this->info("POSLANO: narudžba #{$order->id}, {$sequence}. podsjetnik");
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->error("NEUSPJELO: narudžba #{$order->id}, {$sequence}. podsjetnik — {$exception->getMessage()}");
                }
            }
        }

        if ($dryRun) {
            $this->table(['Narudžba', 'Podsjetnik', 'Vrijeme slanja', 'Jezik', 'E-mail'], $rows);
            $this->info('Dry-run: ' . count($rows) . ' poruka spremno; ništa nije poslano.');

            return 0;
        }

        $this->info("Podsjetnici završeni: {$sent} poslano, {$failed} neuspjelo.");

        return $failed === 0 ? 0 : 1;
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($name, 0, 1) . '***@' . $domain;
    }
}
