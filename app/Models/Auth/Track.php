<?php

namespace App\Models;

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
        return $this->belongsToMany(JobRole::class, 'job_role_tracks')
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
