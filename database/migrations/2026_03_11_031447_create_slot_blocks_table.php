<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->date('blocked_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['slot_id', 'blocked_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_blocks');
    }
};
