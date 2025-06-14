<?php

namespace App\Models\Auth;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Event\Event;
use App\Models\Event\EventStaffAssignment;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\Media\MediaFile;
use App\Models\NotificationsAndMessaging\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


use app\Models\RegistrationAndInterview\InterviewRequest;
use app\Models\RegistrationAndInterview\InterviewQueue;
use app\Models\RegistrationAndInterview\EventRegistration;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles ;
    use HasRoles;


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
    
    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'intake_year' => 'integer',
        'graduation_year' => 'integer',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

      /**
     * User's track
     */
    public function track()
    {
        return $this->belongsTo(Track::class);
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
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
