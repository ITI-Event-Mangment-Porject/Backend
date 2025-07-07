<?php

namespace App\Models\RegistrationAndInterview;

use App\Models\Company\Company;
use App\Models\Auth\User;
use App\Models\JobFair\InterviewSlot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InterviewQueue extends Model
{
    //
    use HasFactory;
    protected $table = 'interview_queue';

    protected $fillable = [
        'interview_request_id', 'company_id', 'user_id', 'slot_id', 'order_key',
        'status', 'interview_started_at', 'interview_ended_at',
        'notes', 'updated_by'
    ];

    protected $casts = [
        'order_key' => 'double',
        'interview_started_at' => 'datetime',
        'interview_ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    protected static function newFactory()
    {
        return \Database\Factories\InterviewQueueFactory::new();
    }

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

    public function slot()
    {
        return $this->belongsTo(InterviewSlot::class, 'slot_id');
    }
}
