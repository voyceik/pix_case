<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_withdraw', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('account_id');
            $table->string('method');
            $table->decimal('amount', 15, 2);
            $table->boolean('scheduled')->default(false);
            $table->dateTime('scheduled_for')->nullable();
            $table->boolean('done')->default(false);
            $table->boolean('error')->default(false);
            $table->string('error_reason')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'created_at'], 'idx_acc_created');
            $table->index(['scheduled', 'scheduled_for', 'done'], 'idx_sched_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_withdraw');
    }
};
