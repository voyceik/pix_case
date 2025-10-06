<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_withdraw_pix', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('account_withdraw_id');
            $table->string('type'); // email
            $table->string('key');
            $table->timestamps();
            $table->unique('account_withdraw_id', 'uniq_withdraw_pix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_withdraw_pix');
    }
};
