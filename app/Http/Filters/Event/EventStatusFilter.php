<?php

namespace App\Http\Filters\Event;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Http\Request;

// Custom filter for event status groups
class EventStatusFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        switch (strtolower($value)) {
            case 'active':
                return $query->whereIn('status', ['published', 'ongoing'])
                           ->where(function ($q) {
                               $q->whereNull('archived_at')
                                 ->orWhere('archived_at', '>', now());
                           });
            
            case 'draft':
                return $query->where('status', 'draft');
            
            case 'archived':
                return $query->where(function ($q) {
                    $q->where('status', 'archived')
                      ->orWhere('archived_at', '<=', now());
                });                
            
            case 'upcoming':
                return $query->where('start_date', '>', now())
                           ->whereIn('status', ['published', 'ongoing']);
            
            default:
                return $query->where('status', $value);
        }
    }
}

