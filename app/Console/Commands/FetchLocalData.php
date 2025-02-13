<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchLocalData extends Command
{
    protected $signature = 'fetch:local-data 
                            {type : Тип данных (orders, sales, incomes, stocks)} 
                            {account_id : ID аккаунта} 
                            {--dateFrom= : Начальная дата (Y-m-d)} 
                            {--dateTo= : Конечная дата (Y-m-d)} 
                            {--bearer= : Токен авторизации Bearer} 
                            {--api-key= : API-ключ} 
                            {--login= : Логин} 
                            {--password= : Пароль}';

    protected $description = 'Делает локальные запросы к API через консоль, аналогично Postman';

    public function handle()
    {
        $type = $this->argument('type');
        $accountId = $this->argument('account_id');
        $dateFrom = $this->option('dateFrom') ?? now()->subDays(7)->format('Y-m-d');
        $dateTo = $this->option('dateTo') ?? now()->format('Y-m-d');
        $bearerToken = $this->option('bearer');
        $apiKey = $this->option('api-key');
        $login = $this->option('login');
        $password = $this->option('password');

        // Определяем URL API
        $baseUrl = 'http://127.0.0.1:8001/api/local-' . $type;
        $queryParams = http_build_query([
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'account_id' => $accountId,
        ]);

        $url = "{$baseUrl}?{$queryParams}";

        $headers = ['Accept' => 'application/json'];

        if ($bearerToken) {
            $headers['Authorization'] = "Bearer {$bearerToken}";
        } elseif ($apiKey) {
            $headers['x-api-Key'] = $apiKey;
        } elseif ($login && $password) {
            $headers['X-Login'] = $login;
            $headers['X-Password'] = $password;
        } else {
            $this->error("Необходимо указать один из способов аутентификации (Bearer, API-Key, Login/Password)");
            return 1;
        }

        // Делаем запрос
        try {
            Log::info("Запрос к API: {$url}");

            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                $this->info("Ответ от API:");
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return 0;
            } else {
                $this->error("Ошибка: " . $response->status());
                $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return 1;
            }
        } catch (\Exception $e) {
            Log::error("Ошибка запроса: " . $e->getMessage());
            $this->error("Ошибка запроса: " . $e->getMessage());
            return 1;
        }
    }
}
