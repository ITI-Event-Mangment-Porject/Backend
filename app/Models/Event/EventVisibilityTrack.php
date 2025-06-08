<?php

namespace App\Models\Event;

use App\Models\Event\Event;
use App\Models\Auth\Track;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventVisibilityTrack extends Model
{
    //
    use HasFactory;
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
