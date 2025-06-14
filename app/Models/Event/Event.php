<?php

namespace App\Models\Event;

use App\Models\Auth\User;
use App\Models\Event\EventSession;
use App\Models\Event\EventStaffAssignment;
use App\Models\Event\EventVisibilityTrack;
use App\Models\FeedbackAndAnalytics\AiInsight;
use App\Models\FeedbackAndAnalytics\FeedbackForm;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\JobFair\JobFairParticipation;
use App\Models\RegistrationAndInterview\EventRegistration;
use App\Models\RegistrationAndInterview\InterviewRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'status',
        'location',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'banner_image',
        'registration_deadline',
        'visibility_type',
        'visibility_config',
        'slido_qr_code',
        'slido_embed_url',
        'created_by',
        'archived_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'registration_deadline' => 'datetime',
        'visibility_config' => 'array',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\EventFactory::new();
    }

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

    // Time formatting helpers
    public function getStartTimeOnlyAttribute()
    {
        return $this->start_time ? $this->start_time->format('H:i:s') : null;
    }

    public function getEndTimeOnlyAttribute()
    {
        return $this->end_time ? $this->end_time->format('H:i:s') : null;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'ongoing'])
                     ->where(function ($query) {
                         $query->whereNull('archived_at')
                               ->orWhere('archived_at', '>', now());
                     });
    }
}
