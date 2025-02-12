<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable();

            $table->unique(['income_id', 'account_id']);

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropUnique(['income_id', 'account_id']);
            $table->dropColumn('account_id');
        });
    }
};
