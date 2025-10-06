<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_idempotency', function (Blueprint $table) {
            $table->string('idempotency_key')->primary();
            $table->string('account_id');
            $table->string('signature');
            $table->string('withdrawal_id')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'created_at'], 'idx_account_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_idempotency');
    }
};
