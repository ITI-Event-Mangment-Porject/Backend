<?php

namespace App\Models\Event;

use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventStaffAssignment extends Model
{
    //
    use HasFactory;
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
