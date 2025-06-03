<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;

class EventVisibilityTrack extends Model
{
    //
    protected $fillable = [
        'event_id', 'track_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
