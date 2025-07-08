<?php

namespace App\Models\NotificationsAndMessaging;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'related_id',
        'related_type', 'is_read', 'sent_via', 'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_via' => 'array',
        'created_at' => 'datetime',
        'read_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\NotificationFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relationship for related entity
    public function related()
    {
        return $this->morphTo();
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    
}

