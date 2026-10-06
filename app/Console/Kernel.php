<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // File-session directory scans must never run inside a storefront
        // request. One low-priority, rate-limited pass may take longer than the
        // interval on large directories; its OS lock prevents overlap too.
        $schedule->command('session:prune-files')
            ->everyFifteenMinutes()
            ->runInBackground()
            ->withoutOverlapping(120);

        $schedule->command('clean:authors')->dailyAt('00:03');
        $schedule->command('clean:publishers')->dailyAt('00:04');
        //
        $schedule->command('reviews:send-requests')
            // Tri pokušaja unutar istog (točno 30.) dana; uspješno poslani se preskaču.
            ->cron('15 10,14,18 * * *')
            ->withoutOverlapping();
        $schedule->command('reviews:process-backfills --max-seconds=58')
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping(5);
        $schedule->command('orders:send-abandoned-cart-reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        $schedule->command('sync:shipment-tracking --limit=50 --stale-minutes=15')
            ->everyFifteenMinutes()
            ->withoutOverlapping();
        $schedule->command('orders:send-notifications --limit=25')
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping(5);
        if (config('services.mailchimp.ecommerce_sync_enabled', false)) {
            $schedule->command('mailchimp:sync-ecommerce-orders --limit=3 --max-seconds=20')
                ->everyFiveMinutes()
                ->withoutOverlapping(5);
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
