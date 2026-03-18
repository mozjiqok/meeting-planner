<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clear table since it's not in production yet, as requested
        DB::table('bookings')->delete();

        Schema::table('bookings', function (Blueprint $table) {
            $table->time('start_time')->after('slot_id');
            $table->unsignedSmallInteger('duration_minutes')->after('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'duration_minutes']);
        });
    }
};
