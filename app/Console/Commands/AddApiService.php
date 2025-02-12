<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiService;
use Illuminate\Support\Facades\Log;

class AddApiService extends Command
{
    protected $signature = 'add:api-service {name} {base_url}';
    protected $description = 'Добавить новый API-сервис';

    public function handle()
    {
        $name = $this->argument('name');
        $baseUrl = $this->argument('base_url');

        $apiService = ApiService::create([
            'name' => $name,
            'base_url' => $baseUrl,
        ]);

        Log::info("API-сервис '{$apiService->name}' добавлен с URL: {$apiService->base_url}.");
        $this->info("API-сервис '{$apiService->name}' добавлен с URL: {$apiService->base_url}!");
    }
}
