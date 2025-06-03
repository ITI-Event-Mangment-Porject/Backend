<?php

namespace App\Models\Job_Fair;

use Illuminate\Database\Eloquent\Model;

class JobProfile extends Model
{
    //
    protected $fillable = [
        'participation_id', 'title', 'description', 'requirements',
        'employment_type', 'location', 'positions_available'
    ];

    protected $casts = [
        'positions_available' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function participation()
    {
        return $this->belongsTo(JobFairParticipation::class, 'participation_id');
    }

    public function trackPreferences()
    {
        return $this->hasMany(JobProfileTrack::class, 'job_role_id');
    }

    public function interviewRequests()
    {
        return $this->hasMany(InterviewRequest::class);
    }
}
