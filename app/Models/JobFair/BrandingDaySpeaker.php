<?php

namespace App\Models\JobFair;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandingDaySpeaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'speaker_name',
        'position',
        'mobile',
        'photo',
        'job_fair_participation_id',
    ];

    public function jobFairParticipation()
    {
        return $this->belongsTo(JobFairParticipation::class, 'job_fair_participation_id');
    }
}
