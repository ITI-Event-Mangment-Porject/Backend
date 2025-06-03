<?php

namespace App\Models\Job_Fair;

use Illuminate\Database\Eloquent\Model;

class JobFairParticipation extends Model
{
    //
    protected $fillable = [
        'event_id', 'company_id', 'status', 'special_requirements',
        'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function jobProfiles()
    {
        return $this->hasMany(JobProfile::class, 'participation_id');
    }

    public function interviewSlots()
    {
        return $this->hasMany(InterviewSlot::class, 'participation_id');
    }
}
