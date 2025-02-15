<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('api_services', function (Blueprint $table) {
            $table->id();
            $table->string('base_url');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('service_name');
            $table->string('api_endpoint');
            $table->timestamps();
            });
    }

    public function down()
    {
        Schema::dropIfExists('api_services');
    }
};
