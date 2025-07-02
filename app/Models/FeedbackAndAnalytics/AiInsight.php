<?php

namespace App\Models\FeedbackAndAnalytics;

use App\Models\Event\Event;
use App\Models\User;
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
        'ai_summary',           // ADD: AI-generated summary
        'is_approved',          // ADD: Admin approval status
        'admin_notes',          // ADD: Admin notes/comments
        'approved_by',          // ADD: Who approved it
        'approved_at',          // ADD: When approved
        'generated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'satisfaction_score' => 'decimal:2',
        'key_themes' => 'array',
        'is_approved' => 'boolean',     // ADD: Cast to boolean
        'generated_at' => 'datetime',
        'approved_at' => 'datetime',    // ADD: Cast to datetime
    ];

    // Relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // ADD: Relationship to user who approved
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes for easier querying
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false)->orWhereNull('approved_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('insight_type', $type);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }

    // Accessor for formatted satisfaction score
    public function getFormattedSatisfactionScoreAttribute()
    {
        return $this->satisfaction_score ? number_format($this->satisfaction_score, 1) . '/5.0' : 'N/A';
    }

    // Accessor to check if insight is fresh (generated within last 24 hours)
    public function getIsFreshAttribute()
    {
        return $this->generated_at && $this->generated_at->diffInHours(now()) < 24;
    }

    // Method to approve insight
    public function approve($userId, $notes = null)
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $userId,
            'approved_at' => now(),
            'admin_notes' => $notes
        ]);
    }

    // Method to get insight status
    public function getStatusAttribute()
    {
        if ($this->is_approved) {
            return 'approved';
        }
        
        if ($this->approved_at === null) {
            return 'pending';
        }
        
        return 'rejected';
    }
}