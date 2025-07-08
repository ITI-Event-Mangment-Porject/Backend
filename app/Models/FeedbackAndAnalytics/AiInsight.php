<?php

namespace App\Models\FeedbackAndAnalytics;

use App\Models\Event\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'insight_type',
        'data',
        'satisfaction_score',
        'key_themes',
        'recommendations',
        'generated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'key_themes' => 'array',
        'satisfaction_score' => 'decimal:2',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\AiInsightFactory::new();
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Helper methods
    public function getAnalysisData(): array
    {
        return is_array($this->data) ? $this->data : json_decode($this->data, true) ?? [];
    }

    public function getKeyThemes(): array
    {
        return is_array($this->key_themes) ? $this->key_themes : json_decode($this->key_themes, true) ?? [];
    }

    public function isApproved(): bool
    {
        $data = $this->getAnalysisData();
        return $data['admin_approved'] ?? false;
    }

    public function getSummary(): string
    {
        $data = $this->getAnalysisData();
        return $data['summary'] ?? 'No summary available';
    }

    public function getRecommendations(): array
    {
        $data = $this->getAnalysisData();
        return $data['recommendations'] ?? [];
    }
}