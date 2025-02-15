<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TokenType;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class AddTokenType extends Command
{
    protected $signature = 'add:token-type {company_name} {name} {type}';
    protected $description = 'Добавить новый тип токена для компании (bearer, api-key, login-password)';

    public function handle()
    {
        $companyName = $this->argument('company_name');
        $name = $this->argument('name');
        $type = $this->argument('type');

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            Log::error("Компания '{$companyName}' не найдена.");
            $this->error("Компания '{$companyName}' не найдена!");
            return 1;
        }

        $existingTokenType = TokenType::where('company_id', $company->id)->where('type', $type)->first();

        if ($existingTokenType) {
            Log::warning("Тип токена '{$type}' уже существует для компании '{$companyName}'.");
            $this->warn("Тип токена '{$type}' уже существует для компании '{$companyName}'.");
            return 1;
        }

        $tokenType = TokenType::create([
            'company_id' => $company->id,
            'name' => $name,
            'type' => $type,
        ]);

        Log::info("Тип токена '{$tokenType->type}' ({$tokenType->name}) добавлен для компании '{$company->name}'.");
        $this->info("Тип токена '{$tokenType->type}' ({$tokenType->name}) добавлен для компании '{$company->name}'!");
    }
}
