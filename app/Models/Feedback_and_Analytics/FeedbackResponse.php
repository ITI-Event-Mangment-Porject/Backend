<?php

namespace App\Models\Feedback_and_Analytics;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FeedbackResponse extends Model
{
    //
    protected $fillable = [
        'form_id', 'user_id', 'event_id', 'responses',
        'overall_rating', 'submitted_at'
    ];

    protected $casts = [
        'responses' => 'array',
        'overall_rating' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(FeedbackForm::class, 'form_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
