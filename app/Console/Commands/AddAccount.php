<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AddAccount extends Command
{
    protected $signature = 'add:account {company_name} {account_name} {email} {password}';

    protected $description = 'Добавить аккаунт в компанию с email и паролем';

    public function handle()
    {
        $companyName = $this->argument('company_name');
        $accountName = $this->argument('account_name');
        $email = $this->argument('email');
        $password = $this->argument('password');

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            Log::error("Компания '{$companyName}' не найдена.");
            $this->error("Компания '{$companyName}' не найдена!");
            return 1;
        }

        // Validate Email and Password
        $validator = Validator::make([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email|unique:accounts,email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $this->error("Ошибка валидации: " . implode(", ", $validator->errors()->all()));
            return 1;
        }

        // Check if account already exists
        $existingAccount = Account::where('company_id', $company->id)->where('name', $accountName)->first();
        if ($existingAccount) {
            Log::warning("Аккаунт '{$accountName}' уже существует в компании '{$companyName}'.");
            $this->warn("Аккаунт '{$accountName}' уже существует в компании '{$companyName}'.");
            return 1;
        }

        // Create account
        $account = Account::create([
            'company_id' => $company->id,
            'name' => $accountName,
            'email' => $email,
            'password' =>$password,
        ]);

        Log::info("Аккаунт '{$account->name}' добавлен в компанию '{$company->name}' с email '{$account->email}'.");
        $this->info("Аккаунт '{$account->name}' добавлен в компанию '{$company->name}' с email '{$account->email}'!");
    }
}
