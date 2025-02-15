<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiService;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class AddApiService extends Command
{
    protected $signature = 'add:api-service {company_name} {service_name} {base_url} {api_endpoint}';
    protected $description = 'Добавить новый API-сервис для компании';

    public function handle()
    {
        $companyName = $this->argument('company_name');
        $serviceName = $this->argument('service_name');
        $baseUrl = $this->argument('base_url');
        $apiEndpoint = $this->argument('api_endpoint');

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            Log::error("Компания '{$companyName}' не найдена.");
            $this->error("Компания '{$companyName}' не найдена!");
            return 1;
        }

        $apiService = ApiService::create([
            'company_id' => $company->id,
            'service_name' => $serviceName,
            'base_url' => $baseUrl,
            'api_endpoint' => $apiEndpoint,
        ]);

        Log::info("API-сервис '{$apiService->service_name}' добавлен в компанию '{$company->name}' с URL: {$apiService->base_url}.");
        $this->info("API-сервис '{$apiService->service_name}' добавлен в компанию '{$company->name}' с URL: {$apiService->base_url}!");
    }
}
