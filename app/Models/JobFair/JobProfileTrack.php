<?php

namespace App\Models\JobFair;

use App\Models\Auth\Track;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobProfileTrack extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'job_profile_id', 'track_id', 'preference_level'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function jobProfile()
    {
        return $this->belongsTo(JobProfile::class, 'job_profile_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
