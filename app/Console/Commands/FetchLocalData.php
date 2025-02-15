<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\TokenType;
use App\Models\ApiToken;
use App\Services\DataService;
use App\Services\ApiServices;

class FetchLocalData extends Command
{
    protected $signature = 'fetch:local-data 
                            {account_name : Название аккаунта} 
                            {api_service_name : Название API-сервиса}
                            {token_type : Тип токена (bearer, api-key, login-password)} 
                            {--dateFrom= : Начальная дата (Y-m-d)} 
                            {--dateTo= : Конечная дата (Y-m-d)} 
                            {--token= : Токен (Bearer, API-Key, Login-Password)}';

    protected $description = 'Запрашивает локальные данные с API и сохраняет их в БД';

    public function handle(DataService $dataService, ApiServices $apiService)
    {
        Log::info("Запрос на получение локальных данных", ['params' => $this->options()]);

        try {
            $accountName = $this->argument('account_name');
            $apiServiceName = $this->argument('api_service_name');
            $tokenType = strtolower($this->argument('token_type'));
            $dateFrom = $this->option('dateFrom') ?? now()->subDays(7)->format('Y-m-d');
            $dateTo = $this->option('dateTo') ?? now()->format('Y-m-d');
            $providedToken = $this->option('token');

            $account = Account::where('name', $accountName)->first();
            if (!$account) {
                Log::error("Аккаунт '{$accountName}' не найден.");
                return 1;
            }

            $apiServiceRecord = ApiService::where('service_name', $apiServiceName)->first();
            if (!$apiServiceRecord) {
                Log::error("API-сервис '{$apiServiceName}' не найден.");
                return 1;
            }

            $tokenTypeRecord = TokenType::where('type', $tokenType)->first();
            if (!$tokenTypeRecord) {
                Log::error("Тип токена '{$tokenType}' не найден.");
                return 1;
            }

            // Check if API token exists
            $apiToken = ApiToken::where('account_id', $account->id)
                ->where('api_service_id', $apiServiceRecord->id)
                ->where('token_type_id', $tokenTypeRecord->id)
                ->first();

            if (!$apiToken) {
                Log::error("API-токен не найден для аккаунта '{$accountName}' и API-сервиса '{$apiServiceName}'.");
                return 1;
            }


            $baseUrl = "{$apiServiceRecord->base_url}/api/{$apiServiceRecord->api_endpoint}";
            $headers = ['Accept' => 'application/json'];
            $page = 1;
            $data = []; //Ensure $data is always initialized

            switch ($tokenType) {
                case 'bearer':
                    $headers['Authorization'] = "Bearer {$providedToken}";
                    break;
                case 'api-key':
                    $headers['x-api-key'] = $providedToken;
                    break;
                case 'login-password':
                    $credentials = explode(':', $providedToken);
                    if (count($credentials) !== 2) {
                        $this->error("Для типа 'login-password' токен должен быть в формате 'login:password'.");
                        return 1;
                    }

                    $login = $credentials[0];
                    $password = $credentials[1];

                    $accountRecord = Account::where('email', $login)->first();
                    if (!$accountRecord || $accountRecord->password !== $password) {
                        Log::error("Ошибка аутентификации: Неверный логин или пароль для '{$login}'.");
                        return 1;
                    }

                    // Send login credentials in headers
                    $headers['X-Login'] = $login;
                    $headers['X-Password'] = $password;
                    break;
                default:
                    $this->error("Недопустимый тип токена: '{$tokenType}'. Используйте 'bearer', 'api-key' или 'login-password'.");
                    return 1;
            }

            $allData = []; 

            do {
                $queryParams = [
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                    'account_id' => $account->id,
                    'page' => $page,
                    'limit' => 100 
                ];
            
                if ($tokenType === 'api-key') {
                    $queryParams['key'] = $providedToken;
                }
            
                $url = "{$baseUrl}?" . http_build_query($queryParams);
            
                // Display request URL in console
                $this->info("Отправляем запрос к API: {$url} (Страница #{$page})");
                Log::info("Запрос к API: {$url} (Страница #{$page})");
            
                $response = $apiService->makeRequestWithRetry($url, $headers);
            
                if ($response->successful()) {
                    $data = $response->json();
            
                    if (!isset($data['data']) || !is_array($data['data']) || count($data['data']) === 0) {
                        $this->info("Данные закончились на странице #{$page}. Завершаем загрузку.");
                        Log::info("Данные закончились на странице #{$page}. Завершаем загрузку.");
                        break;
                    }
            
                    $this->info("Получено " . count($data['data']) . " записей на странице #{$page}.");
                    Log::info("Получено " . count($data['data']) . " записей на странице #{$page}.");
            
                    // Append New Data Instead of Overwriting
                    $allData = array_merge($allData, $data['data']);
            
                    // Process & Save Each Page's Data Incrementally
                    switch ($apiServiceRecord->api_endpoint) {
                        case 'orders':
                            $dataService->saveOrders($allData, $account->id);
                            break;
                        case 'sales':
                            $dataService->saveSales($allData, $account->id);
                            break;
                        case 'incomes':
                            $dataService->saveIncomes($allData, $account->id);
                            break;
                        case 'stocks':
                            $dataService->saveStocks($allData, $account->id);
                            break;
                        default:
                            $this->error("Неизвестный API-сервис: '{$apiServiceRecord->api_endpoint}'.");
                            Log::error("Неизвестный API-сервис: '{$apiServiceRecord->api_endpoint}'.");
                            return 1;
                    }
            
                    // Ensure Pagination Continues
                    if (!isset($data['links']['next']) || $data['links']['next'] === null) {
                        $this->info("Последняя страница достигнута (#{$page}), завершаем загрузку.");
                        Log::info("Последняя страница достигнута (#{$page}), завершаем загрузку.");
                        break;
                    }
            
                    // Free Memory After Processing Each Page
                    unset($data);
                    gc_collect_cycles();
            
                    $page++;
            
                } else {
                    $this->error("Ошибка API: " . $response->status());
                    Log::error("Ошибка API: " . $response->status());
                    return 1;
                }
            
            } while (true);
            

            

            Log::info("Данные успешно сохранены.");
            return 0;

        } catch (\Exception $e) {
            Log::error("Ошибка запроса: " . $e->getMessage());
            return 1;
        }
    }
}
