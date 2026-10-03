<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('campaign_recipient_id');
            $table->string('recipient_email', 191);
            $table->string('subject', 255);
            $table->mediumText('message');
            $table->string('attachment_path', 500)->nullable();
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('campaign_recipient_id')->references('id')->on('campaign_recipients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_queue');
    }
};
