<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraints extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        Schema::table('token_types', function (Blueprint $table) {
            $table->dropUnique(['type']);
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->dropUnique(['token_value']);
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::table('token_types', function (Blueprint $table) {
            $table->unique('type');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->unique('token_value');
        });
    }
}
