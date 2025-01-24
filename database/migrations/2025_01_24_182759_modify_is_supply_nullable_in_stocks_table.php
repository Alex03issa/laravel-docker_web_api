<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->boolean('is_supply')->nullable()->default(null)->change()->after('quantity');
            $table->boolean('is_realization')->nullable()->default(null)->change()->after('is_supply');
        });
    }
    
    public function down()
    {
        Schema::table('stocks', function (Blueprint $table) {
            DB::statement('UPDATE stocks SET is_supply = 0 WHERE is_supply IS NULL');
            DB::statement('UPDATE stocks SET is_realization = 0 WHERE is_realization IS NULL');
    
            $table->boolean('is_supply')->nullable(false)->default(0)->change();
            $table->boolean('is_realization')->nullable(false)->default(0)->change();
        });
    }
    
};
