<?php

namespace App\Models\Notifications_and_Messaging;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //
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
