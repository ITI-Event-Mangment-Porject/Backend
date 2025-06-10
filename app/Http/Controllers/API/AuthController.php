<?php

namespace App\Http\Controllers\API;

use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseApiController
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'=>'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            
            'password' => 'required|string|min:8|confirmed',
            'portal_user_id'=>'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'=>$request->last_name,
            'email' => $request->email,
            'portal_user_id'=>$request->portal_user_id,
            'password' => Hash::make($request->password)]);
            

        $token = JWTAuth::fromUser($user);

        return $this->sendResponse([
            'user' => $user->only(['id', 'name', 'email']),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'expires_at' => now()->addMinutes((float) config('jwt.ttl'))->toDateTimeString()
        ], 'User registered successfully.');
        
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }        $credentials = $request->only('email', 'password');
        
        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials'], 401);
        }

        return $this->sendResponse([
            'user' => JWTAuth::user()->only(['id', 'name', 'email']),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ], 'User logged in successfully.');
    }

    /**
     * Get authenticated user's profile
     */    public function profile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $this->sendResponse([
                'user' => $user->only(['id', 'name', 'email'])
            ], 'Profile retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Token Error.', ['error' => 'Invalid token'], 401);
        }
    }

    /**
     * Logout user
     */    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->sendResponse([], 'User logged out successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Logout Error.', ['error' => 'Failed to logout'], 500);
        }
    }

    /**
     * Refresh token
     */    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            return $this->sendResponse([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ], 'Token refreshed successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Refresh Error.', ['error' => 'Failed to refresh token'], 401);
        }
    }
}
