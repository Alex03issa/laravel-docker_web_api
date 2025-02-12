<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\TokenType;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Log;

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
        $apiService = ApiService::where('name', $apiServiceName)->first();
        $tokenType = TokenType::where('type', $tokenTypeName)->first();

        if (!$account || !$apiService || !$tokenType) {
            Log::error("Ошибка! Проверьте названия аккаунта, API-сервиса и типа токена.");
            $this->error("Ошибка! Проверьте названия аккаунта, API-сервиса и типа токена.");
            return 1;
        }

        $apiToken = ApiToken::create([
            'account_id' => $account->id,
            'api_service_id' => $apiService->id,
            'token_type_id' => $tokenType->id,
            'token_value' => $tokenValue,
        ]);

        Log::info("Токен для API '{$apiService->name}' добавлен в аккаунт '{$account->name}'.");
        $this->info("Токен для API '{$apiService->name}' добавлен в аккаунт '{$account->name}'!");
    }
}
