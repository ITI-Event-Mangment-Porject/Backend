<?php

namespace App\Models\JobFair;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSlot extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'participation_id', 'slot_date', 'start_time', 'end_time',
        'duration_minutes', 'max_interviews_per_slot', 'is_break',
        'break_reason', 'is_available'
    ];

    protected $casts = [
        'slot_date' => 'date',
        'duration_minutes' => 'integer',
        'max_interviews_per_slot' => 'integer',
        'is_break' => 'boolean',
        'is_available' => 'boolean',
        'created_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\InterviewSlotFactory::new();
    }

    public function participation()
    {
        return $this->belongsTo(JobFairParticipation::class, 'participation_id');
    }
}
