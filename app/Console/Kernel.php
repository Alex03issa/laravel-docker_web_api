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
       
        $fromDate = now()->subDay()->format('Y-m-d H:i:s');
        $toDate = now()->format('Y-m-d H:i:s');

        $schedule->command("fetch:api-data --type=orders --fromDate='{$fromDate}' --toDate='{$toDate}'")->dailyAt('01:00');
        $schedule->command("fetch:api-data --type=sales --fromDate='{$fromDate}' --toDate='{$toDate}'")->dailyAt('01:15');
        $schedule->command("fetch:api-data --type=incomes --fromDate='{$fromDate}' --toDate='{$toDate}'")->dailyAt('01:30');
        $schedule->command('fetch:api-data --type=stocks')->dailyAt('02:00');
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
