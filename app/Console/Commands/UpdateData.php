<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateData extends Command
{
   
    protected $signature = 'update:data';

   
    protected $description = 'Обновляет данные из API дважды в день';

    public function __construct()
    {
        parent::__construct();
    }

    
    public function handle()
    {
        Log::info("Начало обновления данных...");

        try {
            $fromDateYesterday = now()->subDays(1)->format('Y-m-d');
            $toDateToday = now()->format('Y-m-d');

            Log::info("Обновляем все данные за период: {$fromDateYesterday} - {$toDateToday}");

            // Обновляем все данные за вчерашний день
            $this->callWithLogging('fetch:api-data', [
                '--type' => 'all',
                '--fromDate' => $fromDateYesterday,
                '--toDate' => $toDateToday
            ]);

            Log::info("Обновляем stocks только за сегодня: {$toDateToday}");

            // Отдельно обновляем stocks только на сегодня
            $this->callWithLogging('fetch:api-data', [
                '--type' => 'stocks',
                '--fromDate' => $toDateToday,
                '--toDate' => $toDateToday
            ]);

            Log::info("Обновление данных завершено успешно.");
        } catch (\Exception $e) {
            Log::error("Ошибка при обновлении данных: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    
    private function callWithLogging($command, $params)
    {
        try {
            Log::info("Запуск команды: {$command} с параметрами: ", $params);
            $this->call($command, $params);
            Log::info("Команда {$command} успешно выполнена.");
        } catch (\Exception $e) {
            Log::error("Ошибка при выполнении команды {$command}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
