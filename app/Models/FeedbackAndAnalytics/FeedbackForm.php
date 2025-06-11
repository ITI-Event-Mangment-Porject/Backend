<?php

namespace App\Models\FeedbackAndAnalytics;

use App\Models\Event\Event;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackForm extends Model
{
    //
    use HasFactory;
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
    protected static function newFactory()
    {
        return \Database\Factories\FeedbackFormFactory::new();
    }

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
