<?php

namespace Database\Seeders;

use App\Models\Slot;
use Illuminate\Database\Seeder;

class SlotSeeder extends Seeder
{
    /**
     * Seed the two daily slots: 07:30 and 17:30, Mon–Fri
     */
    public function run(): void
    {
        $times = ['07:30:00', '17:30:00'];

        foreach (range(1, 5) as $dow) { // 1=Mon, 5=Fri
            foreach ($times as $time) {
                Slot::firstOrCreate(
                    ['day_of_week' => $dow, 'start_time' => $time],
                    ['duration_minutes' => 30, 'is_active' => true]
                );
            }
        }
    }
}
