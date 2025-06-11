<?php

namespace App\Models\RegistrationAndInterview;

use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Models\JobFair\JobProfile;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewRequest extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'event_id', 'user_id', 'job_profile_id', 'company_id',
        'status', 'message', 'requested_at', 'reviewed_at',
        'reviewed_by', 'notes'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\InterviewRequestFactory::new();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobProfile()
    {
        return $this->belongsTo(JobProfile::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function queueEntry()
    {
        return $this->hasOne(InterviewQueue::class);
    }
}
