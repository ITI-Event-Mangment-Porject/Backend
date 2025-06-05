<?php

namespace App\Models\Job_Fair;

use App\Models\Track;
use Illuminate\Database\Eloquent\Model;

class JobProfileTrack extends Model
{
    //
    protected $fillable = [
        'job_role_id', 'track_id', 'preference_level'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function jobProfile()
    {
        return $this->belongsTo(JobProfile::class, 'job_role_id');
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
