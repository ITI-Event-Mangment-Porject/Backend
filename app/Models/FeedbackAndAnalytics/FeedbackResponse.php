<?php

namespace App\Models\FeedbackAndAnalytics;

use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackResponse extends Model
{
    //
    use HasFactory;
    protected $fillable = [
        'form_id', 'user_id', 'event_id', 'responses',
        'overall_rating', 'submitted_at'
    ];

    protected $casts = [
        'responses' => 'array',
        'overall_rating' => 'integer',
        'submitted_at' => 'datetime',
    ];
    protected static function newFactory()
    {
        return \Database\Factories\FeedbackResponseFactory::new();
    }

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
