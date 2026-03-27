<?php

namespace App\Console\Commands;

use App\Models\Back\Catalog\Product\Product;
use App\Services\MailchimpEcommerceService;
use Illuminate\Console\Command;

class SyncProductsToMailchimp extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mailchimp:sync-products {--chunk=100} {--only-active=1} {--only-stocked=1}';

    /**
     * @var string
     */
    protected $description = 'Sync catalog products to the Mailchimp e-commerce store.';

    public function handle(MailchimpEcommerceService $mailchimp): int
    {
        if (! $mailchimp->isConfigured()) {
            $this->error('Mailchimp e-commerce nije konfiguriran.');

            return self::FAILURE;
        }

        $chunk = max((int) $this->option('chunk'), 1);
        $onlyActive = filter_var($this->option('only-active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $onlyStocked = filter_var($this->option('only-stocked'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $query = Product::query()->orderBy('id');

        if ($onlyActive !== false) {
            $query->where('status', 1)->where('price', '>', 0);
        }

        if ($onlyStocked !== false) {
            $query->where('quantity', '>', 0);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nema artikala za Mailchimp product sync.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $errorSamples = [];

        $query->chunkById($chunk, function ($products) use ($mailchimp, &$ok, &$failed, &$errorSamples) {
            foreach ($products as $product) {
                $result = $mailchimp->syncCatalogProduct($product);

                if ($result['ok']) {
                    $ok++;

                    continue;
                }

                if (count($errorSamples) < 3 && ! empty($result['error'])) {
                    $errorSamples[] = 'Product ' . $product->id . ': ' . $result['error'];
                }

                $failed++;
            }
        });

        $message = 'Mailchimp product sync gotov. Ukupno: ' . $total
            . ', uspješno: ' . $ok
            . ', greške: ' . $failed . '.';

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        $this->info($message);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
