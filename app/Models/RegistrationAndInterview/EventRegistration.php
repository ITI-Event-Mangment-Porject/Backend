<?php

namespace App\Models\RegistrationAndInterview;
use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'event_id', 'user_id', 'status', 'registration_type',
        'registered_at', 'cancelled_at', 'cancellation_reason',
        'checked_in_at', 'checked_in_by', 'check_in_method'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\EventRegistrationFactory::new();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    // Helper methods
    public function isAttended()
    {
        return $this->status === 'attended';
    }

    public function isCheckedIn()
    {
        return !is_null($this->checked_in_at);
    }
}
