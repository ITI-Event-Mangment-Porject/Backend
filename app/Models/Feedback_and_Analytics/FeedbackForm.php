<?php

namespace App\Models\Feedback_and_Analytics;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FeedbackForm extends Model
{
    //
    protected $fillable = [
        'event_id', 'title', 'description', 'form_config',
        'is_active', 'created_by'
    ];

    protected $casts = [
        'form_config' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(FeedbackResponse::class, 'form_id');
    }
}
