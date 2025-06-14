<?php

namespace App\Http\Filters\Event;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Support\Carbon;

class EventDateRangeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        // Support string format: "2024-01-01,2024-12-31" or array
        $dates = is_string($value) ? explode(',', $value) : (array) $value;

        if (count($dates) !== 2) {
            return $query; // Invalid input length — skip
        }

        try {
            $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
            $endDate = Carbon::parse(trim($dates[1]))->endOfDay();
        } catch (\Exception $e) {
            return $query; // Invalid date format — skip
        }

        // Date range logic
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }
}

