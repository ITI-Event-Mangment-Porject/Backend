<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Registration_and_interview\EventRegistration;
use App\Models\Event\EventStaffAssignment;
use App\Models\Registration_and_interview\InterviewQueue;
use App\Models\Registration_and_interview\InterviewRequest;
use App\Models\Media\MediaFile;
use App\Models\Notifications_and_Messaging\Notification;
use App\Models\Feedback_and_Analytics\FeedbackResponse;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles ;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'portal_user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'profile_image',
        'cv_path',
        'bio',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'track_id',
        'intake_year',
        'graduation_year',
        'is_active',
        'last_login_at',
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'intake_year' => 'integer',
            'graduation_year' => 'integer',
        ];
    }
    
      /**
     * User's track
     */
    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    /**
     * User roles relationship
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot(['assigned_by', 'assigned_at', 'is_active'])
                    ->wherePivot('is_active', true);
    }
    public function eventRegistrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function staffAssignments()
    {
        return $this->hasMany(EventStaffAssignment::class);
    }

    public function interviewRequests()
    {
        return $this->hasMany(InterviewRequest::class);
    }

    public function interviewQueue()
    {
        return $this->hasMany(InterviewQueue::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function feedbackResponses()
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function uploadedFiles()
    {
        return $this->hasMany(MediaFile::class, 'uploaded_by');
    }

    // Helper methods
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
