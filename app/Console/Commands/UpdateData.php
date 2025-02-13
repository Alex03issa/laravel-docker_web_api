<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Account; 

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
            $account = Account::first();

            if (!$account) {
                Log::error("Нет доступных аккаунтов для обновления данных.");
                return;
            }

            $accountId = $account->id;

            $fromDateYesterday = now()->subDays(1)->format('Y-m-d');
            $toDateToday = now()->format('Y-m-d');

            Log::info("Обновляем все данные за период: {$fromDateYesterday} - {$toDateToday} для account_id: {$accountId}");

            // Обновляем все данные за вчерашний день
            $this->callWithLogging('fetch:api-data', [
                '--type' => 'all',
                '--fromDate' => $fromDateYesterday,
                '--toDate' => $toDateToday,
                '--accountId' => $accountId,
            ]);

            Log::info("Обновляем stocks только за сегодня: {$toDateToday}");

            // Отдельно обновляем stocks только на сегодня
            $this->callWithLogging('fetch:api-data', [
                '--type' => 'stocks',
                '--fromDate' => $toDateToday,
                '--toDate' => $toDateToday,
                '--accountId' => $accountId,
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
