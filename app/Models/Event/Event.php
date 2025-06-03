<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //
    protected $fillable = [
        'title', 'slug', 'description', 'type', 'status', 'location',
        'start_date', 'end_date', 'start_time', 'end_time', 'banner_image',
        'registration_deadline', 'visibility_type', 'visibility_config',
        'slido_qr_code', 'slido_embed_url', 'created_by', 'archived_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'datetime',
        'visibility_config' => 'array',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions()
    {
        return $this->hasMany(EventSession::class)->orderBy('start_time');
    }

    public function staffAssignments()
    {
        return $this->hasMany(EventStaffAssignment::class);
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function jobFairParticipations()
    {
        return $this->hasMany(JobFairParticipation::class);
    }

    public function interviewRequests()
    {
        return $this->hasMany(InterviewRequest::class);
    }

    public function feedbackForms()
    {
        return $this->hasMany(FeedbackForm::class);
    }

    public function feedbackResponses()
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function aiInsights()
    {
        return $this->hasMany(AiInsight::class);
    }

    public function visibilityTracks()
    {
        return $this->hasMany(EventVisibilityTrack::class);
    }

    // Helper methods
    public function isJobFair()
    {
        return $this->type === 'Job Fair';
    }

    public function isActive()
    {
        return in_array($this->status, ['published', 'ongoing']);
    }
}
