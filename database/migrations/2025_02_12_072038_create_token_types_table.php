<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('token_types', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // Example: 'bearer', 'api-key', 'basic-auth'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('token_types');
    }
};
