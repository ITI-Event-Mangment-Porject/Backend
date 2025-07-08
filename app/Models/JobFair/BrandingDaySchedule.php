<?php

namespace App\Models\JobFair;

use App\Models\Company\Company;
use App\Models\Event\Event;
use Illuminate\Database\Eloquent\Model;

class BrandingDaySchedule extends Model
{
    protected $fillable = [
        'event_id',
        'company_id',
        'participation_id',
        'branding_day_date',
        'start_time',
        'end_time',
        'order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function participation()
    {
        return $this->belongsTo(JobFairParticipation::class, 'participation_id');
    }

    public function speaker()
    {
        return $this->belongsTo(BrandingDaySpeaker::class, 'branding_day_speaker_id');
    }
}
