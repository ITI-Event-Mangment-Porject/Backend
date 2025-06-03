<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;

class EventStaffAssignment extends Model
{
    //
    protected $fillable = [
        'event_id', 'user_id', 'role', 'assigned_by', 'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
