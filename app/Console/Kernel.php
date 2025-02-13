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
        $schedule->command('update:data')
            ->timezone('Europe/Moscow')
            ->twiceDailyAt(8, 18, 00)
            ->before(function () {
                $this->waitForDatabase();
            })
            ->onFailure(function () {
                \Log::error( 'Ошибка при обновлении данных!');
            });
    }

    /**
     * Waits for MySQL to be ready before running scheduled jobs
     */
    private function waitForDatabase()
    {
        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                \DB::connection()->getPdo();
                \Log::info("MySQL is ready. Starting scheduled task...");
                return;
            } catch (\Exception $e) {
                $attempt++;
                \Log::warning("MySQL not ready. Retrying attempt {$attempt}/{$maxAttempts}...");
                sleep(10);
            }
        }

        \Log::error("MySQL is not available after {$maxAttempts} attempts. Skipping scheduled task.");
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
