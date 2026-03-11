<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlotBlock extends Model
{
    protected $fillable = [
        'slot_id',
        'blocked_date',
        'reason',
    ];

    protected $casts = [
        'blocked_date' => 'date',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }
}
