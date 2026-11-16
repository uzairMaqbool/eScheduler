<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        /*
        |--------------------------------------------------------------------------
        | AUTO CLOSE EVENTS
        |--------------------------------------------------------------------------
        |
        | Automatically close published events after
        | their end date has passed.
        |
        */
        $schedule->command('events:auto-close')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        /*
        |--------------------------------------------------------------------------
        | EXPIRE MEETING REQUESTS
        |--------------------------------------------------------------------------
        |
        | Automatically expire pending meeting requests
        | once their expiry time has elapsed.
        |
        */
        $schedule->command('meetings:expire')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        /*
        |--------------------------------------------------------------------------
        | SEND MEETING REMINDERS
        |--------------------------------------------------------------------------
        |
        | Notify participants about accepted meetings
        | scheduled to start within the next 15 minutes.
        |
        */
        $schedule->command('meetings:reminders')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
