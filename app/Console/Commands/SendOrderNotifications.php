<?php

namespace App\Console\Commands;

use App\Models\Back\Orders\Order;
use App\Models\OrderNotificationDelivery;
use App\Services\OrderNotificationService;
use Illuminate\Console\Command;

class SendOrderNotifications extends Command
{
    protected $signature = 'orders:send-notifications
                            {order_ids?* : Brojevi narudžbi; bez njih se obrađuje red čekanja}
                            {--status : Samo prikaži status zadanih narudžbi}
                            {--force : Ponovno pošalji i već uspješno poslanu obavijest}
                            {--admin-only : Obradi samo administratorsku obavijest}
                            {--customer-only : Obradi samo potvrdu kupcu}
                            {--limit=25 : Najveći broj zapisa iz reda čekanja}';

    protected $description = 'Provjerava i pouzdano šalje administratorske i kupčeve obavijesti o narudžbi';

    public function handle(OrderNotificationService $service): int
    {
        if (! $service->isAvailable()) {
            $this->error('Nedostaje order_notification_deliveries tablica ili potrebni stupci. Primijenite migraciju/SQL 040.');

            return 1;
        }

        if ($this->option('admin-only') && $this->option('customer-only')) {
            $this->error('Opcije --admin-only i --customer-only ne mogu se koristiti zajedno.');

            return 1;
        }

        $orderIds = $this->orderIds();
        if ($orderIds === null) {
            return 1;
        }

        $kinds = $this->kinds();

        if ($orderIds === []) {
            if ($this->option('status')) {
                $this->error('Opcija --status zahtijeva barem jedan broj narudžbe.');

                return 1;
            }

            if ($this->option('force')) {
                $this->error('Opcija --force dopuštena je samo uz izričito zadane brojeve narudžbi.');

                return 1;
            }

            return $this->processQueue($service, $kinds);
        }

        if ($this->option('status') && $this->option('force')) {
            $this->error('Opcije --status i --force ne mogu se koristiti zajedno.');

            return 1;
        }

        return $this->option('status')
            ? $this->showStatuses($service, $orderIds, $kinds)
            : $this->sendOrders($service, $orderIds, $kinds, (bool) $this->option('force'));
    }

    private function processQueue(OrderNotificationService $service, array $kinds): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $maxSeconds = max(1, (int) config('order_notifications.max_seconds', 50));
        $summary = $service->processPending($limit, $maxSeconds, $kinds);

        foreach ($summary['results'] as $result) {
            $label = sprintf(
                '#%s %s → %s',
                $result['order_id'] ?: '?',
                $result['kind'],
                $result['status']
            );

            if ($result['status'] === 'sent') {
                $this->info($label);
            } elseif (in_array($result['status'], ['failed', 'permanent_failure'], true)) {
                $this->error($label . ($result['error'] ? ' — ' . $result['error'] : ''));
            } else {
                $this->line($label);
            }
        }

        $this->info(sprintf(
            'Red obrađen: %d odabrano, %d pokušano, %d poslano, %d neuspjelo, %d odgođeno, %d preskočeno.',
            $summary['selected'],
            $summary['attempted'],
            $summary['sent'],
            $summary['failed'],
            $summary['deferred'],
            $summary['skipped']
        ));

        return $summary['failed'] === 0 ? 0 : 1;
    }

    private function showStatuses(OrderNotificationService $service, array $orderIds, array $kinds): int
    {
        $orders = Order::query()->whereIn('id', $orderIds)->get()->keyBy('id');
        $rows = [];
        $missing = false;

        foreach ($orderIds as $orderId) {
            $order = $orders->get($orderId);
            if (! $order) {
                $missing = true;
                $rows[] = [$orderId, '—', 'narudžba ne postoji', 0, '—', '—', '—', '—'];
                continue;
            }

            $status = $service->statusFor($order);
            foreach ($kinds as $kind) {
                $delivery = $status['deliveries'][$kind];
                $rows[] = [
                    $orderId,
                    $kind,
                    $delivery['status'],
                    $delivery['attempts'],
                    $this->maskEmail((string) $delivery['recipient_email']),
                    $this->formatDate($delivery['available_at']),
                    $this->formatDate($delivery['sent_at']),
                    $delivery['last_error'] ?: '—',
                ];
            }
        }

        $this->table(
            ['Narudžba', 'Vrsta', 'Status', 'Pokušaji', 'Primatelj', 'Dostupno', 'Poslano', 'Zadnja greška'],
            $rows
        );

        return $missing ? 1 : 0;
    }

    private function sendOrders(
        OrderNotificationService $service,
        array $orderIds,
        array $kinds,
        bool $force
    ): int {
        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->get()
            ->keyBy('id');
        $failed = 0;

        foreach ($orderIds as $orderId) {
            $order = $orders->get($orderId);
            if (! $order) {
                $failed++;
                $this->error("Narudžba #{$orderId} ne postoji.");
                continue;
            }

            try {
                $results = $service->sendForOrder($order, $kinds, $force);
            } catch (\Throwable $exception) {
                $failed++;
                $this->error("Narudžba #{$orderId}: {$exception->getMessage()}");
                continue;
            }

            foreach ($results as $result) {
                $label = "Narudžba #{$orderId}, {$result['kind']}: {$result['status']}";

                if (in_array($result['status'], ['sent', 'sent_legacy'], true)) {
                    $this->info($label);
                } elseif ($result['status'] === 'already_sent' || $result['status'] === 'sent') {
                    $this->line($label);
                } elseif (in_array($result['status'], ['busy', 'deferred'], true)) {
                    $failed++;
                    $this->warn($label);
                } elseif ($result['status'] === 'pending') {
                    $this->line($label);
                } else {
                    $failed++;
                    $this->error($label . ($result['error'] ? ' — ' . $result['error'] : ''));
                }
            }
        }

        return $failed === 0 ? 0 : 1;
    }

    private function orderIds()
    {
        $ids = [];

        foreach ((array) $this->argument('order_ids') as $value) {
            foreach (explode(',', (string) $value) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate === '') {
                    continue;
                }

                if (! ctype_digit($candidate) || (int) $candidate < 1) {
                    $this->error("Neispravan broj narudžbe: {$candidate}");

                    return null;
                }

                $ids[(int) $candidate] = true;
            }
        }

        return array_keys($ids);
    }

    private function kinds(): array
    {
        if ($this->option('admin-only')) {
            return [OrderNotificationDelivery::KIND_ADMIN];
        }

        if ($this->option('customer-only')) {
            return [OrderNotificationDelivery::KIND_CUSTOMER];
        }

        return [
            OrderNotificationDelivery::KIND_ADMIN,
            OrderNotificationDelivery::KIND_CUSTOMER,
        ];
    }

    private function formatDate($value): string
    {
        if (! $value) {
            return '—';
        }

        return method_exists($value, 'format') ? $value->format('d.m.Y. H:i:s') : (string) $value;
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || strpos($email, '@') === false) {
            return $email !== '' ? 'neispravan' : '—';
        }

        list($name, $domain) = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 1) . '***@' . $domain;
    }
}
