<?php
/**
 * Migration to fix unique constraint on bookings.
 *
 * This change allows multiple cancelled bookings for the same slot and date,
 * but enforces only one confirmed booking per slot and date.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Drop the old unique constraint that included status
            $table->dropUnique(['slot_id', 'booking_date', 'status']);

            // Create a new partial unique index for confirmed bookings only
            // This ensures only one person can book a specific slot at a specific date
            DB::statement('CREATE UNIQUE INDEX bookings_confirmed_unique ON bookings (slot_id, booking_date) WHERE status = "confirmed"');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_confirmed_unique');
            $table->unique(['slot_id', 'booking_date', 'status']);
        });
    }
};
