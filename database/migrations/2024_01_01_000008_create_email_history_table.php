<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_email', 191);
            $table->string('subject', 255);
            $table->enum('status', ['sent', 'pending', 'failed']);
            $table->dateTime('sent_at')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->foreign('recipient_id')->references('id')->on('recipients')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_history');
    }
};
