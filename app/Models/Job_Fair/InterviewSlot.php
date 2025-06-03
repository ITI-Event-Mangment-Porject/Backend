<?php

namespace App\Models\Job_Fair;

use Illuminate\Database\Eloquent\Model;

class InterviewSlot extends Model
{
    //
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

    public function participation()
    {
        return $this->belongsTo(JobFairParticipation::class, 'participation_id');
    }
}
