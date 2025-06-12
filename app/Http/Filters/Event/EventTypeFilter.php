<?php

namespace App\Http\Filters\Event;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Http\Request;





// Custom filter for event types with job fair logic
class EventTypeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        switch (strtolower($value)) {
            case 'job_fair':
            case 'job-fair':
                return $query->where('type', 'Job Fair');
            
            case 'tech':
                return $query->where('type', 'Tech');
            
            case 'fun':
                return $query->where('type', 'fun');
            
            default:
                return $query->where('type', $value);
        }
    }
}