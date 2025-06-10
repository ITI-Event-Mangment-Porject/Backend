<?php

namespace App\Models\NotificationsAndMessaging;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkMessage extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'title', 'message', 'target_criteria', 'sent_by',
        'total_recipients', 'sent_count', 'failed_count',
        'status', 'scheduled_at', 'sent_at'
    ];

    protected $casts = [
        'target_criteria' => 'array',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
