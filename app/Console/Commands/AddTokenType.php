<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TokenType;
use Illuminate\Support\Facades\Log;

class AddTokenType extends Command
{
    protected $signature = 'add:token-type {type}';
    protected $description = 'Добавить новый тип токена (bearer, api-key, login-password)';

    public function handle()
    {
        $type = $this->argument('type');

        $tokenType = TokenType::create([
            'type' => $type,
        ]);

        Log::info("Тип токена '{$tokenType->type}' добавлен.");
        $this->info("Тип токена '{$tokenType->type}' добавлен!");
    }
}
