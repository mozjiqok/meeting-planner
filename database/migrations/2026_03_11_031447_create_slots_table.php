<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            // 1=Monday, 5=Friday
            $table->tinyInteger('day_of_week')->unsigned(); // 1-5
            $table->time('start_time'); // e.g. 07:30:00
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->string('default_meeting_url')->nullable();
            $table->timestamps();

            $table->unique(['day_of_week', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
