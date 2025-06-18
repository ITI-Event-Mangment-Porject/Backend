<?php

namespace App\Models\JobFair;

use App\Models\RegistrationAndInterview\InterviewRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobProfile extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'participation_id', 'title', 'description', 'requirements',
        'employment_type', 'location', 'positions_available'
    ];

    protected $casts = [
        'positions_available' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\JobProfileFactory::new();
    }

    public function participation()
    {
        return $this->belongsTo(JobFairParticipation::class, 'participation_id');
    }

    public function trackPreferences()
    {
        return $this->hasMany(JobProfileTrack::class, 'job_profile_id');
    }

    public function interviewRequests()
    {
        return $this->hasMany(InterviewRequest::class);
    }
    public function isEmpty()
    {
        return $this->positions_available <= 0;
    }
    public function isFull()
    {
        return $this->positions_available <= 0;
    }
}
