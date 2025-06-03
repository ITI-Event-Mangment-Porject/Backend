<?php

namespace App\Models\Feedback_and_Analytics;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    //
    protected $fillable = [
        'event_id', 'insight_type', 'data', 'satisfaction_score',
        'key_themes', 'recommendations', 'generated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'satisfaction_score' => 'decimal:2',
        'key_themes' => 'array',
        'generated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
