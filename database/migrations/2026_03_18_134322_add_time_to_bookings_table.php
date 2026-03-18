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
        Schema::table('bookings', function (Blueprint $table) {
            $table->time('start_time')->after('slot_id')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->after('start_time')->default(0);
        });

        // Populate existing bookings from the slots table
        DB::table('bookings')
            ->join('slots', 'bookings.slot_id', '=', 'slots.id')
            ->update([
                'bookings.start_time'       => DB::raw('slots.start_time'),
                'bookings.duration_minutes' => DB::raw('slots.duration_minutes'),
            ]);
        
        Schema::table('bookings', function (Blueprint $table) {
            $table->time('start_time')->nullable(false)->change();
            $table->unsignedSmallInteger('duration_minutes')->nullable(false)->change();
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
