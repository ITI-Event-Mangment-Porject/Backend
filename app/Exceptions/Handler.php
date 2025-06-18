<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        
        // if ($shouldReturnJson) {//
        // }
    });
    
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Force JSON response for API routes, AJAX requests, or when JSON is expected
        // Also force JSON if we're dealing with common API-related exceptions
        $shouldReturnJson = $request->is('api/*') || 
                           $request->expectsJson() || 
                           $request->wantsJson() || 
                           $request->ajax() ||
                           $request->header('Accept') === 'application/json' ||
                           $exception instanceof AuthenticationException ||
                           $exception instanceof JWTException ||
                           $exception instanceof UnauthorizedHttpException;
            
            // Handle HttpResponseException first (Laravel's own responses)
            if ($exception instanceof HttpResponseException) {
                return $exception->getResponse();
            }

            // 401 - Authentication errors
            if ($exception instanceof AuthenticationException) {
                return $this->handleUnauthenticated($request, $exception);
            }

            // JWT specific errors
            if ($exception instanceof JWTException) {
                return $this->handleJWTException($exception);
            }

            // 401 - Unauthorized HTTP exceptions
            if ($exception instanceof UnauthorizedHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Unauthorized access.',
                    'errors' => [],
                ], 401);
            }

            // 403 - Authorization/Forbidden errors
            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'You do not have permission to perform this action.',
                    'errors' => [],
                ], 403);
            }

            // 404 - Not Found errors
            if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception instanceof ModelNotFoundException ? 
                        'The requested resource was not found.' : 
                        ($exception->getMessage() ?: 'Resource not found.'),
                    'errors' => [],
                ], 404);
            }

            // 405 - Method not allowed
            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'HTTP method not allowed for this route.',
                    'errors' => [],
                ], 405);
            }

            // 422 - Validation errors
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $exception->validator->errors(),
                ], 422);
            }

            // Handle other HTTP exceptions
            if ($exception instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'An error occurred.',
                    'errors' => [],
                ], $exception->getStatusCode());
            }

            // Log unexpected errors for debugging
            Log::error('Unexpected API exception', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request_url' => $request->fullUrl(),
                'request_method' => $request->method(),
                'request_data' => $request->except(['password', 'password_confirmation', 'current_password']),
            ]);

            // 500 - Server error fallback
            $message = config('app.debug') ? 
                'Server Error: ' . $exception->getMessage() : 
                'An internal server error occurred.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [],
            ], 500);
        

        // For non-API requests, use default Laravel handling
        return parent::render($request, $exception);
    }

    /**
     * Handle JWT specific exceptions
     */
    protected function handleJWTException(JWTException $exception)
    {
        if ($exception instanceof TokenExpiredException) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please refresh your token.',
                'errors' => ['token' => ['expired']],
            ], 401);
        }

        if ($exception instanceof TokenInvalidException) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid. Please login again.',
                'errors' => ['token' => ['invalid']],
            ], 401);
        }

        if ($exception instanceof TokenBlacklistedException) {
            return response()->json([
                'success' => false,
                'message' => 'Token has been blacklisted. Please login again.',
                'errors' => ['token' => ['blacklisted']],
            ], 401);
        }

        // Generic JWT error
        return response()->json([
            'success' => false,
            'message' => 'Token error: ' . $exception->getMessage(),
            'errors' => ['token' => ['error']],
        ], 401);
    }

    /**
     * Handle unauthenticated requests
     */
    protected function handleUnauthenticated($request, AuthenticationException $exception)
    {
        // Always return JSON for API routes, AJAX requests, or when JSON is expected
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
                'errors' => [],
            ], 401);
        }

        // For web requests, check if login route exists before redirecting
        // if (\Route::has('login')) {
        //     return redirect()->guest(route('login'));
        // }

        // If no login route exists, return JSON response anyway
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Please log in.',
            'errors' => [],
        ], 401);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     * This method is called by Laravel's authentication middleware
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->handleUnauthenticated($request, $exception);
    }

    /**
     * Convert a validation exception into a JSON response.
     * This method is called automatically for validation errors on API routes
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $exception->validator->errors(),
        ], 422);
    }
}
