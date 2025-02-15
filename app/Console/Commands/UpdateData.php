<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\ApiToken;
use App\Models\TokenType;

class UpdateData extends Command
{
    protected $signature = 'update:data';
    protected $description = 'Обновляет данные из API для всех компаний, их аккаунтов и сервисов';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info("🔄 Начало обновления данных для всех компаний...");

        try {
            $companies = Company::all();

            if ($companies->isEmpty()) {
                Log::error("❌ Нет доступных компаний для обновления данных.");
                return;
            }

            $fromDateYesterday = now()->subDays(1)->format('Y-m-d');
            $toDateToday = now()->format('Y-m-d');

            foreach ($companies as $company) {
                Log::info("📌 Обновление данных для компании: {$company->name} (ID: {$company->id})");

                $accounts = Account::where('company_id', $company->id)->get();
                if ($accounts->isEmpty()) {
                    Log::warning("⚠ Компания '{$company->name}' не имеет аккаунтов. Пропускаем.");
                    continue;
                }

                $apiServices = ApiService::where('company_id', $company->id)->get();
                if ($apiServices->isEmpty()) {
                    Log::warning("⚠ Компания '{$company->name}' не имеет API-сервисов. Пропускаем.");
                    continue;
                }

                foreach ($accounts as $account) {
                    $accountId = $account->id;
                    $accountName = $account->name;

                    // 🔹 Process all API services before handling `stocks`
                    foreach ($apiServices as $apiService) {
                        // Skip stocks, as it will be handled separately
                        if ($apiService->api_endpoint === 'stocks') {
                            continue;
                        }

                        $apiServiceName = $apiService->service_name;
                        $apiEndpoint = $apiService->api_endpoint;

                        $tokenType = TokenType::where('company_id', $company->id)->first();
                        if (!$tokenType) {
                            Log::warning("⚠ Пропуск API-сервиса '{$apiServiceName}' для аккаунта '{$accountName}', тип токена не найден.");
                            continue;
                        }

                        $apiToken = ApiToken::where('account_id', $accountId)
                            ->where('api_service_id', $apiService->id)
                            ->where('token_type_id', $tokenType->id)
                            ->first();

                        if (!$apiToken) {
                            Log::warning("⚠ Пропуск API-сервиса '{$apiServiceName}' для аккаунта '{$accountName}', API-токен отсутствует.");
                            continue;
                        }

                        $tokenValue = $apiToken->token_value;

                        Log::info("🔄 Обновление данных `{$apiEndpoint}` для API-сервиса '{$apiServiceName}', аккаунт '{$accountName}'.");

                        $this->callWithLogging('fetch:local-data', [
                            'account_name' => $accountName,
                            'api_service_name' => $apiServiceName,
                            'token_type' => $tokenType->type,
                            '--dateFrom' => $fromDateYesterday,
                            '--dateTo' => $toDateToday,
                            '--token' => $tokenValue 
                        ]);
                    }

                    // 🔹 Handle stocks update AFTER processing all other services
                    Log::info("📌 Обновляем `stocks` только за сегодня: {$toDateToday}");

                    $stocksService = ApiService::where('company_id', $company->id)
                        ->where('api_endpoint', 'stocks')
                        ->first();

                    if ($stocksService) {
                        $stockToken = ApiToken::where('account_id', $accountId)
                            ->where('api_service_id', $stocksService->id)
                            ->first();

                        if ($stockToken) {
                            $this->callWithLogging('fetch:local-data', [
                                'account_name' => $accountName,
                                'api_service_name' => 'stocks',
                                'token_type' => 'api-key',
                                '--dateFrom' => $toDateToday,
                                '--dateTo' => $toDateToday,
                                '--token' => $stockToken->token_value
                            ]);
                        } else {
                            Log::warning("⚠ Пропуск обновления `stocks` для аккаунта '{$accountName}', API-токен отсутствует.");
                        }
                    } else {
                        Log::warning("⚠ Пропуск обновления `stocks`, API-сервис `stocks` не найден.");
                    }
                }
            }

            Log::info("✅ Обновление данных завершено успешно.");
        } catch (\Exception $e) {
            Log::error("❌ Ошибка при обновлении данных: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function callWithLogging($command, $params)
    {
        try {
            Log::info("🚀 Запуск команды: {$command} с параметрами: ", $params);
            $this->call($command, $params);
            Log::info("✅ Команда {$command} успешно выполнена.");
        } catch (\Exception $e) {
            Log::error("❌ Ошибка при выполнении команды {$command}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
