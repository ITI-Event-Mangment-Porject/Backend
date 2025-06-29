<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnyRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = auth()->user();

        // Split comma roles
        if (count($roles) === 1 && str_contains($roles[0], ',')) {
            $roles = explode(',', $roles[0]);
        }

        // Trim
        $roles = array_map('trim', $roles);

        // Check roles manually using hasRole
        foreach ($roles as $role) {
            if ($user && $user->hasRole($role)) {
                return $next($request);
            }
        }

        // Custom response instead of Spatie exception
        return response()->json([
            'message' => 'User does not have any of the required roles.',
        ], Response::HTTP_FORBIDDEN);
    }
}
