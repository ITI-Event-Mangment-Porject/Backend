<?php

namespace App\Models\Company;

use App\Models\Auth\User;
use App\Models\Job_Fair\JobFairParticipation;
use App\Models\Registration_and_interview\InterviewQueue;
use App\Models\Registration_and_interview\InterviewRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Company extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'name', 'logo_path', 'description', 'website', 'industry',
        'size', 'location', 'contact_email', 'contact_phone', 
        'linkedin_url', 'is_approved', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\CompanyFactory::new();
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function jobFairParticipations()
    {
        return $this->hasMany(JobFairParticipation::class);
    }

    public function interviewRequests()
    {
        return $this->hasMany(InterviewRequest::class);
    }

    public function interviewQueues()
    {
        return $this->hasMany(InterviewQueue::class);
    }
}
