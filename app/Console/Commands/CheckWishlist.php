<?php

namespace App\Console\Commands;

use App\Models\Back\Marketing\Wishlist;
use Illuminate\Console\Command;

class CheckWishlist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:wishlist
                            {--dry-run : Prikaži što bi bilo poslano bez upisa i slanja}
                            {--limit= : Najveći broj obavijesti u ovom pokretanju}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check wishlist & send emails if product is available.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        if (! config('wishlist.emails_enabled') && ! $dryRun) {
            $this->warn('Wishlist slanje je isključeno (WISHLIST_EMAILS_ENABLED=false).');

            return 0;
        }

        $limit = $this->option('limit') !== null
            ? max(1, min((int) $this->option('limit'), 1000))
            : ($dryRun ? null : max(1, (int) config('wishlist.notification_batch_size', 50)));
        $result = Wishlist::check_CRON($dryRun, $limit);

        $prefix = $dryRun ? 'Dry-run wishlist' : 'Wishlist';
        $this->info(sprintf(
            '%s: obavijesti %d, zapisa %d, neispravnih adresa %d, neuspjelo %d.',
            $prefix,
            $result['notifications'],
            $result['entries'],
            $result['invalid'],
            $result['failed']
        ));

        return $result['failed'] > 0 ? 1 : 0;
    }
}
