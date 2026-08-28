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
            ->everyMinute()
            ->withoutOverlapping();
        $schedule->command('sync:shipment-tracking --limit=50 --stale-minutes=15')
            ->everyFifteenMinutes()
            ->withoutOverlapping();
        $schedule->command('mailchimp:sync-ecommerce-orders --limit=5 --max-seconds=50')
            ->everyMinute()
            ->withoutOverlapping(5);
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
