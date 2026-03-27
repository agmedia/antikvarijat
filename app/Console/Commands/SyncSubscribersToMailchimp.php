<?php

namespace App\Console\Commands;

use App\Models\Back\Marketing\NewsletterSubscriber;
use App\Services\MailchimpNewsletterService;
use Illuminate\Console\Command;

class SyncSubscribersToMailchimp extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mailchimp:sync-subscribers {--chunk=200}';

    /**
     * @var string
     */
    protected $description = 'Sync pending newsletter subscribers to Mailchimp.';

    public function handle(MailchimpNewsletterService $mailchimp): int
    {
        if (! $mailchimp->isConfigured()) {
            $this->error('Mailchimp newsletter nije konfiguriran.');

            return self::FAILURE;
        }

        $chunk = max((int) $this->option('chunk'), 1);

        $query = NewsletterSubscriber::query()
            ->where('status', 1)
            ->where('gdpr', 1)
            ->whereNull('mailchimp_synced_at')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nema newsletter pretplatnika za Mailchimp sync.');

            return self::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $errorSamples = [];

        $query->chunkById($chunk, function ($subscribers) use ($mailchimp, &$ok, &$failed, &$errorSamples) {
            foreach ($subscribers as $subscriber) {
                $result = $mailchimp->syncSubscriber($subscriber);

                if ($result['ok']) {
                    $subscriber->update([
                        'mailchimp_synced_at' => now(),
                        'mailchimp_last_error' => null,
                    ]);
                    $ok++;

                    continue;
                }

                $subscriber->update([
                    'mailchimp_last_error' => $result['error'],
                ]);

                if (count($errorSamples) < 3 && ! empty($result['error'])) {
                    $errorSamples[] = 'Subscriber ' . $subscriber->id . ': ' . $result['error'];
                }

                $failed++;
            }
        });

        $message = 'Mailchimp subscriber sync gotov. Ukupno: ' . $total
            . ', uspješno: ' . $ok
            . ', greške: ' . $failed . '.';

        if (! empty($errorSamples)) {
            $message .= ' Primjeri: ' . implode(' | ', array_unique($errorSamples));
        }

        $this->info($message);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
