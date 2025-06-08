<?php

namespace App\Models\Event;

use App\Models\Event\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'event_id', 'title', 'description', 'speaker_name', 'speaker_bio',
        'speaker_image', 'start_time', 'end_time', 'location', 
        'session_order', 'is_break'
    ];

    protected $casts = [
        'session_order' => 'integer',
        'is_break' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\EventSessionFactory::new();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
