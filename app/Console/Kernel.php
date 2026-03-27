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
    protected $commands = [
        \App\Console\Commands\SyncAbandonedCartsToMailchimp::class,
        \App\Console\Commands\SyncSubscribersToMailchimp::class,
        \App\Console\Commands\SyncProductsToMailchimp::class,
        \App\Console\Commands\SyncOrdersToMailchimp::class,
    ];

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
        $schedule->command('check:wishlist')->everySixHours();//->everyMinute();
        $schedule->command('mailchimp:sync-abandoned-carts --minutes=60 --limit=200')->everyFifteenMinutes();
        $schedule->command('mailchimp:sync-subscribers --chunk=200')->hourly();
        $schedule->command('mailchimp:sync-products --chunk=100 --only-active=1 --only-stocked=1')->dailyAt('02:20');
        $schedule->command('mailchimp:sync-orders --days=7 --chunk=100')->dailyAt('02:35');
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
