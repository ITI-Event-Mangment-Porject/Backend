<?php

namespace App\Models\Auth;

use App\Models\Event\EventVisibilityTrack;
use App\Models\JobFair\JobProfile;
use App\Models\JobFair\JobProfileTrack;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Track extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\TrackFactory::new();
    }

    // ============ RELATIONSHIPS ============

    /**
     * Users in this track
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Job roles that prefer this track
     */
    public function jobRoles()
    {
        return $this->belongsToMany(JobProfile::class, 'job_profile_track')
                    ->withPivot('preference_level')
                    ->withTimestamps();
    }
    public function jobProfileTracks()
    {
        return $this->hasMany(JobProfileTrack::class);
    }

    public function eventVisibilityTracks()
    {
        return $this->hasMany(EventVisibilityTrack::class);
    }
}
