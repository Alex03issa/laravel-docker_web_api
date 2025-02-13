<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class AddAccount extends Command
{
    protected $signature = 'add:account {company_name} {account_name}';

    protected $description = 'Добавить аккаунт в компанию';

    public function handle()
    {
        $companyName = $this->argument('company_name');
        $accountName = $this->argument('account_name');

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            Log::error("Компания '{$companyName}' не найдена.");
            $this->error("Компания '{$companyName}' не найдена!");
            return 1;
        }

        $existingAccount = Account::where('company_id', $company->id)->where('name', $accountName)->first();

        if ($existingAccount) {
            Log::warning("Аккаунт '{$accountName}' уже существует в компании '{$companyName}'.");
            $this->warn("Аккаунт '{$accountName}' уже существует в компании '{$companyName}'.");
            return 1;
        }

        $account = Account::create([
            'company_id' => $company->id,
            'name' => $accountName,
        ]);

        Log::info("Аккаунт '{$account->name}' добавлен в компанию '{$company->name}'.");
        $this->info("Аккаунт '{$account->name}' добавлен в компанию '{$company->name}'!");
    }
}
