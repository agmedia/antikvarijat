<?php

namespace App\Console\Commands;

use App\Mail\BookPurchaseMessage;
use App\Mail\ContactFormMessage;
use App\Mail\ContractWithdrawalAdminMail;
use App\Mail\ContractWithdrawalReceiptMail;
use App\Mail\OrderReceived;
use App\Mail\OrderSent;
use App\Mail\ProductReviewRequestMail;
use App\Mail\StatusCanceled;
use App\Mail\StatusPaid;
use App\Mail\WishlistArrived;
use App\Models\Back\Orders\Order;
use App\Models\ContractWithdrawal;
use App\Models\Front\Catalog\Product;
use App\Models\ProductReviewInvitation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendTestEmailDesigns extends Command
{
    protected $signature = 'mail:test-design
                            {recipient : Adresa na koju se šalju testni mailovi}
                            {--only=all : all ili popis odvojen zarezom}
                            {--send : Potvrda stvarnog slanja}';

    protected $description = 'Šalje sigurne testne primjerke email predložaka bez promjene poslovnih zapisa';

    public function handle(): int
    {
        $recipient = mb_strtolower(trim((string) $this->argument('recipient')));
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Primatelj mora biti valjana e-mail adresa.');

            return 1;
        }

        if (! $this->option('send')) {
            $this->warn('Ništa nije poslano. Za stvarno testno slanje dodajte opciju --send.');

            return 1;
        }

        app()->setLocale('hr');

        $available = [
            'wishlist',
            'review',
            'order-customer',
            'order-admin',
            'order-paid',
            'order-canceled',
            'password',
            'contact',
            'book-purchase',
            'withdrawal-receipt',
            'withdrawal-admin',
        ];
        $selected = $this->selectedTemplates((string) $this->option('only'), $available);
        if ($selected === null) {
            return 1;
        }

        $needsProduct = count(array_intersect($selected, ['wishlist'])) > 0;
        $product = $needsProduct
            ? Product::query()->active()->available()->basicData()->orderByDesc('updated_at')->first()
            : null;
        if ($needsProduct && ! $product) {
            $this->error('Nije pronađen aktivan artikl na stanju za wishlist test.');

            return 1;
        }

        $orderTemplates = ['review', 'order-customer', 'order-admin', 'order-paid', 'order-canceled'];
        $needsOrder = count(array_intersect($selected, $orderTemplates)) > 0;
        $order = $needsOrder
            ? Order::query()
                ->whereHas('products')
                ->whereHas('totals')
                ->with(['products.product', 'totals'])
                ->orderByRaw("CASE WHEN payment_code = 'bank' THEN 1 ELSE 0 END")
                ->orderByDesc('id')
                ->first()
            : null;
        if ($needsOrder && ! $order) {
            $this->error('Nije pronađena narudžba s artiklima i iznosima za test.');

            return 1;
        }

        $invitation = new ProductReviewInvitation();
        if ($order) {
            $invitation->forceFill([
                'order_id' => $order->id,
                'recipient_email' => $recipient,
                'recipient_name' => 'Tomislav',
                'locale' => 'hr',
                'eligible_at' => now(),
            ]);
        }

        $withdrawal = new ContractWithdrawal([
            'reference' => 'TEST-' . now()->format('Ymd-His'),
            'order_number' => $order ? (string) $order->id : 'TEST-10001',
            'full_name' => 'Tomislav — testni prikaz',
            'email' => $recipient,
            'phone' => '+385 91 000 0000',
            'address_line' => 'Testna ulica 1',
            'postal_code' => '10000',
            'city' => 'Zagreb',
            'country_code' => 'HR',
            'contract_date' => now()->subDays(7),
            'received_date' => now()->subDays(3),
            'items' => 'Testni artikl — ovaj zapis nije spremljen u bazu.',
            'note' => 'Vizualni test email predloška.',
            'declaration' => 'Ovo je testni prikaz izjave o jednostranom raskidu ugovora.',
            'locale' => 'hr',
            'submitted_at' => now(),
        ]);

        $jobs = [
            'wishlist' => fn () => Mail::to($recipient)->send((new WishlistArrived($product))->locale('hr')),
            'review' => fn () => Mail::to($recipient)->send((new ProductReviewRequestMail(
                $invitation,
                config('app.url')
            ))->locale('hr')),
            'order-customer' => fn () => Mail::to($recipient)->send((new OrderSent($order))->locale('hr')),
            'order-admin' => fn () => Mail::to($recipient)->send((new OrderReceived($order))->locale('hr')),
            'order-paid' => fn () => Mail::to($recipient)->send((new StatusPaid($order))->locale('hr')),
            'order-canceled' => fn () => Mail::to($recipient)->send((new StatusCanceled($order))->locale('hr')),
            'password' => function () use ($recipient) {
                Mail::send('emails.forget-password', ['token' => Str::random(64)], function ($message) use ($recipient) {
                    $message->to($recipient)->subject('[TEST] Resetiranje lozinke — Antikvarijat Biblos');
                });
            },
            'contact' => fn () => Mail::to($recipient)->send((new ContactFormMessage([
                'name' => 'Tomislav — testni prikaz',
                'email' => $recipient,
                'phone' => '+385 91 000 0000',
                'message' => "Ovo je testna poruka kontakt forme.\nNijedan stvarni upit nije stvoren.",
            ]))->locale('hr')),
            'book-purchase' => fn () => Mail::to($recipient)->send((new BookPurchaseMessage([
                'full_name' => 'Tomislav — testni prikaz',
                'postal_code' => '10000 Zagreb',
                'email' => $recipient,
                'phone' => '+385 91 000 0000',
                'submission_id' => 'TEST-' . now()->format('YmdHis'),
                'submitted_at' => now()->format('d.m.Y. H:i'),
                'photos' => [[
                    'name' => 'Testna fotografija (poveznica vodi na naslovnicu)',
                    'url' => config('app.url'),
                ]],
            ]))->locale('hr')),
            'withdrawal-receipt' => fn () => Mail::to($recipient)->send((new ContractWithdrawalReceiptMail(
                $withdrawal,
                [
                    'return_address' => "Antikvarijat Biblos\nPalmotićeva 28\n10000 Zagreb",
                    'instructions' => 'Testna uputa za prikaz predloška.',
                ],
                'Trošak povrata snosi kupac.'
            ))->locale('hr')),
            'withdrawal-admin' => fn () => Mail::to($recipient)->send((new ContractWithdrawalAdminMail(
                $withdrawal,
                config('app.url') . '/admin/contract-withdrawals'
            ))->locale('hr')),
        ];

        $sent = 0;
        foreach ($selected as $template) {
            try {
                $jobs[$template]();
                $sent++;
                $this->info("POSLANO: {$template}");
            } catch (\Throwable $exception) {
                $this->error("NEUSPJELO: {$template} — {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Testni mailovi poslani na {$recipient}: {$sent}/" . count($selected) . '.');
        $this->warn('Testni review/reset/admin linkovi služe samo za vizualni pregled i ne stvaraju poslovne zapise.');

        return $sent === count($selected) ? 0 : 1;
    }

    private function selectedTemplates(string $only, array $available): ?array
    {
        if ($only === '' || $only === 'all') {
            return $available;
        }

        $selected = array_values(array_unique(array_filter(array_map('trim', explode(',', $only)))));
        $invalid = array_values(array_diff($selected, $available));
        if ($invalid !== []) {
            $this->error('Nepoznati predlošci: ' . implode(', ', $invalid));
            $this->line('Dostupno: ' . implode(', ', $available));

            return null;
        }

        return $selected;
    }
}
