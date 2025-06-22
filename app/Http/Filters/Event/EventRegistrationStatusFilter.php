<?php

namespace App\Http\Filters\Event;

use Illuminate\Database\Eloquent\Builder;

use Spatie\QueryBuilder\Filters\Filter;

use Illuminate\Http\Request;

// Custom filter for registration status
class EventRegistrationStatusFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        switch (strtolower($value)) {
            case 'open':
                return $query->where('registration_deadline', '>', now())
                           ->whereIn('status', ['published', 'ongoing']);
            
            case 'closed':
                return $query->where('registration_deadline', '<=', now())
                           ->orWhereIn('status', ['draft', 'archived']);
            case 'pending':
                return $query->whereHas('registrations', function ($q) {
                    $q->where('status', 'pending');
                });
            
            default:
                return $query;
        }
    }
}
