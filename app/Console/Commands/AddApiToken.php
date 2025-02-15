<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\TokenType;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AddApiToken extends Command
{
    protected $signature = 'add:api-token {account} {api_service} {token_type} {token_value}';
    protected $description = 'Добавить новый API-токен';

    public function handle()
    {
        $accountName = $this->argument('account');
        $apiServiceName = $this->argument('api_service');
        $tokenTypeName = $this->argument('token_type');
        $tokenValue = $this->argument('token_value');

        $account = Account::where('name', $accountName)->first();
        $apiService = ApiService::where('service_name', $apiServiceName)->first();
        $tokenType = TokenType::where('type', $tokenTypeName)->first();

        if (!$account) {
            Log::error("Аккаунт '{$accountName}' не найден.");
            $this->error("Аккаунт '{$accountName}' не найден.");
            return 1;
        }

        if (!$apiService) {
            Log::error("API-сервис '{$apiServiceName}' не найден.");
            $this->error("API-сервис '{$apiServiceName}' не найден.");
            return 1;
        }

        if (!$tokenType) {
            Log::error("Тип токена '{$tokenTypeName}' не найден.");
            $this->error("Тип токена '{$tokenTypeName}' не найден.");
            return 1;
        }

        $apiToken = ApiToken::create([
            'account_id' => $account->id,
            'api_service_id' => $apiService->id,
            'token_type_id' => $tokenType->id,
            'token_value' => $tokenValue,
        ]);

        Log::info("Токен для API '{$apiService->service_name}' добавлен в аккаунт '{$account->name}'.");
        $this->info("Токен для API '{$apiService->service_name}' добавлен в аккаунт '{$account->name}'!");
    }
}
