<?php

namespace App\Models\Registration_and_interview;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;


class InterviewQueue extends Model
{
    //
    protected $fillable = [
        'interview_request_id', 'company_id', 'user_id', 'queue_position',
        'status', 'interview_started_at', 'interview_ended_at',
        'notes', 'updated_by'
    ];

    protected $casts = [
        'queue_position' => 'integer',
        'interview_started_at' => 'datetime',
        'interview_ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function interviewRequest()
    {
        return $this->belongsTo(InterviewRequest::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
