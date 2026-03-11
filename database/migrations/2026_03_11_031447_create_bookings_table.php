<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->date('booking_date');
            $table->bigInteger('telegram_user_id');
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->json('answers'); // {q1: "...", q2: "...", q3: "..."}
            $table->string('meeting_url')->nullable();
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->boolean('reminder_24h_sent')->default(false);
            $table->boolean('reminder_1h_sent')->default(false);
            $table->timestamps();

            // One booking per slot per date
            $table->unique(['slot_id', 'booking_date', 'status']);
            $table->index('telegram_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
