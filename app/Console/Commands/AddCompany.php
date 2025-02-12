<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class AddCompany extends Command
{
    protected $signature = 'add:company {name} {description?}';
    protected $description = 'Добавить новую компанию';

    public function handle()
    {
        $name = $this->argument('name');
        $description = $this->argument('description') ?? 'Без описания';

        $company = Company::create([
            'name' => $name,
            'description' => $description,
        ]);

        Log::info("Компания '{$company->name}' добавлена.");
        $this->info("Компания '{$company->name}' добавлена!");
    }
}
