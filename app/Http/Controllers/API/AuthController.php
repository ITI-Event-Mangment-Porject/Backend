<?php

namespace App\Http\Controllers\API;

use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\LoginRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
use Illuminate\Support\Facades\Log;


// use Illuminate\Support\Facades\Http;
/**
 * Class AuthController
 * Handles user authentication, login, logout, token refresh, and profile retrieval.
 */


class AuthController extends BaseApiController
{
    /**
     * User login
     */
    private $httpClient;
    
    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }
    
    public function login(LoginRequest $request)
    {
        $validated = $request->validated(); 
        
        $email = $validated['email'];
        $password = $validated['password'];
        
        try {
            // Send credentials to Portal system
            // $portalResponse = $this->authenticateWithPortal($email, $password);
            
            $portalResponse = [
                'success' => true,
                
                'data' => [
                    'token' => 'mocked_token',
                    'user' => [
                            'id' => 'PU48494',
                            'role' => 'admin',
                            'email' => $email,
                            'first_name' => 'admin',
                            'last_name' => 'admin',
                            'cv_path' => 'cv.pdf',
                            'phone' => '01012345678',
                            'track_id' => 1,
                            'intake_year' => 2023,
                            'graduation_year' => 2024,
                            'bio' => 'Sample Bio',
                            'linkedin_url' => 'https://linkedin.com/in/ahmed',
                            'github_url' => 'https://github.com/ahmed',
                            'portfolio_url' => 'https://ahmed.com',
                            'profile_image' => 'profile.jpg',
                        ],
                            
                    ],
                'message' => 'Authenticated successfully',
                ];
            
            
            if (!$portalResponse['success']) {
                return $this->sendError($portalResponse['message'], [], 401);
            }
            
            $portalData = $portalResponse['data'];
            $portalToken = $portalData['token'];
            $userData = $portalData['user'];
            
            // Validate required fields from portal response
            if (!isset($userData['id'], $userData['role'], $userData['first_name'], $userData['last_name'], $userData['email'])) {
                return $this->sendError('Invalid user data from portal', [], 400);
            }  
            
            if (!is_string($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->sendError('Invalid email format.');
            }
            $role = $userData['role'] === 'alumni' ? 'student' : $userData['role']; 
            // Check if user exists in local database
            $user = User::where('portal_user_id', $userData['id'])->first();
            
            if (!$user) {
                // Create new user
                $user = User::create([
                    'portal_user_id' => $userData['id'],
                    'email' => $userData['email'],
                    'cv_path' => $userData['cv_path'] ?? null,
                    'first_name' => $userData['first_name'] ?? null,
                    'last_name' => $userData['last_name'] ?? null,
                    'phone' => $userData['phone'] ?? null,
                    'track_id' => $userData['track_id'] ?? null,
                    'intake_year' => $userData['intake_year'] ?? null,
                    'graduation_year' => $userData['graduation_year'] ?? null,
                    'bio' => $userData['bio'] ?? null,
                    'linkedin_url' => $userData['linkedin_url'] ?? null,
                    'github_url' => $userData['github_url'] ?? null,
                    'portfolio_url' => $userData['portfolio_url'] ?? null,
                    'profile_image' => $userData['profile_image'] ?? null,
                ]);
                $user->assignRole($role);
                
                $accessToken = JWTAuth::fromUser($user);
                $refreshToken = JWTAuth::customClaims([
                    'type' => 'refresh',
                    'exp' => now()->addDays(30)->timestamp  // 30 days expiry
                ])->fromUser($user);
                
                return $this->sendResponse([
                    'user' => $user,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'portal_token' => $portalToken,
                    'profile_complete' => false,
                ], 'User created. Profile completion required.');
            }
            
            // Update existing user with latest data from portal
            $user->update([
                'email' => $userData['email'],
                'cv_path' => $userData['cv_path'] ?? $user->cv_path,
                'first_name' => $userData['first_name'] ?? $user->first_name,
                'last_name' => $userData['last_name'] ?? $user->last_name,
                'phone' => $userData['phone'] ?? $user->phone,
                'track_id' => $userData['track_id'] ?? $user->track_id,
                'intake_year' => $userData['intake_year'] ?? $user->intake_year,
                'graduation_year' => $userData['graduation_year'] ?? $user->graduation_year,
                'bio' => $userData['bio'] ?? $user->bio,
                'linkedin_url' => $userData['linkedin_url'] ?? $user->linkedin_url,
                'github_url' => $userData['github_url'] ?? $user->github_url,
                'portfolio_url' => $userData['portfolio_url'] ?? $user->portfolio_url,
                'profile_image' => $userData['profile_image'] ?? $user->profile_image,
            ]);
            $user->syncRoles($role);
            
            // Generate local tokens
            $accessToken = JWTAuth::fromUser($user);
            $refreshToken = JWTAuth::customClaims([
                'type' => 'refresh',
                'exp' => now()->addDays(30)->timestamp  // 30 days expiry
            ])->fromUser($user);
            
            // Check profile completion
            $profileComplete = $this->checkProfileComplete($user);
            
            return $this->sendResponse([
                'user' => $user,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'portal_token' => $portalToken,
                'profile_complete' => $profileComplete
            ], 'Login successful');
            
        }
         catch (ConnectException $e) {
            return $this->sendError('Unable to connect to authentication server', [], 503);
        } catch (RequestException $e) {
            return $this->sendError('Authentication failed', [], 401);
        } 
        catch (ExpiredException $e) {
            return $this->sendError('Token expired', [$e->getMessage()], 401);
        } catch (SignatureInvalidException $e) {
            return $this->sendError('Invalid token signature', [$e->getMessage()], 401);
        } catch (DomainException | UnexpectedValueException $e) {
            return $this->sendError('Token could not be processed', [$e->getMessage()], 400);
        }catch (InvalidArgumentException | BeforeValidException $e) {
            return $this->sendError('Token is not valid yet', [$e->getMessage()], 400);
        }
        catch (JWTException $e) {
            // This happens when token is not sent at all
            return $this->sendError('Unauthorized', ['error' => 'Token is missing or not provided'], 401);
    
        }
         catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->sendError('Login failed', [], 500);
        }
    }
    
    private function authenticateWithPortal($email, $password)
    {
        try {
            $response = $this->httpClient->post(env('PORTAL_API_URL') . '/api/auth/login', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Key' => env('PORTAL_API_KEY'), // if required
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);
            
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            
            if ($statusCode === 200 && isset($body['token']) && isset($body['data'])) {
                return $this->sendResponse($body, 'Authenticated successfully');
            }
    
            return $this->sendError($body['message'] ?? 'Authentication failed',[], 401);
            
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $body = json_decode($response->getBody()->getContents(), true);
                return $this->sendError($body['message'] ?? 'Invalid credentials',[], $response->getStatusCode());
            }
    
            return $this->sendError('Connection to portal failed',[], 500);
        }
        catch (ConnectException $e) {
            return $this->sendError('Unable to connect to authentication server', [], 503);
        } catch (\Exception $e) {
            Log::error('Portal authentication error: ' . $e->getMessage());
            return $this->sendError('Authentication failed', [], 500);
        }
    }
    
    
    // private function authenticateWithPortal($email, $password)
    // {
    //     try {
    //         $response = Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //             'Accept' => 'application/json',
    //             'X-API-Key' => env('PORTAL_API_KEY'),
    //         ])->post(env('PORTAL_API_URL') . '/api/auth/login', [
    //             'email' => $email,
    //             'password' => $password,
    //         ]);
        
    //         if ($response->successful()) {
    //             $body = $response->json();
            
    //             if (isset($body['token'], $body['data'])) {
    //                 return [
    //                     'success' => true,
    //                     'data' => $body,
    //                     'message' => 'Authenticated successfully',
    //                 ];
    //             }
            
    //             return [
    //                 'success' => false,
    //                 'message' => $body['message'] ?? 'Invalid portal response',
    //             ];
    //         }
        
    //         return [
    //             'success' => false,
    //             'message' => $response->json()['message'] ?? 'Authentication failed',
    //         ];
        
    //     } catch (\Exception $e) {
    //         Log::error('Portal authentication error: ' . $e->getMessage());
    //         return [
    //             'success' => false,
    //             'message' => 'Connection to portal failed: ' . $e->getMessage(),
    //         ];
    //     }
    // }
    
    private function checkProfileComplete($user)
    {
        $requiredFields = [
            'cv_path', 'first_name', 'last_name', 'phone', 'track_id',
            'intake_year', 'graduation_year', 'bio', 'linkedin_url',
            'github_url', 'portfolio_url', 'profile_image'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) return false;
        }
        return true;
    }
    
    
    
    /**
     * Refresh JWT token
     */
    public function refresh()
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            if ($payload->get('type') !== 'refresh') {
                return $this->sendError('Invalid token type', [], 401);
            }
            $token = JWTAuth::parseToken()->refresh();
            return $this->sendResponse([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ], 'Token refreshed successfully.');
        }  catch (TokenInvalidException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token is invalid'], 401);
    
        } catch (JWTException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token is missing or not provided'], 401);
    
        } catch (\Exception $e) {
            return $this->sendError('Server Error.', ['error' => 'Something went wrong'], 500);
        }
    }
    
    /**
     * User logout
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->sendResponse([], 'User logged out successfully.');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token has expired'], 401);
    
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token is invalid'], 401);
    
        } catch (JWTException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token is missing or not provided'], 401);
    
        } catch (\Exception $e) {
            return $this->sendError('Server Error.', ['error' => 'Something went wrong'], 500);
        }
    }
    /**
     * Get authenticated user's profile
     */    
    public function profile(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $this->sendResponse([
                'user' => $user->load('roles'),
            ], 'Profile retrieved successfully.');
        } catch (TokenExpiredException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token has expired'], 401);
    
        } catch (TokenInvalidException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token is invalid'], 401);
    
        } catch (JWTException $e) {
            return $this->sendError('Token Error.', ['error' => 'Token is missing or not provided'], 401);
    
        } catch (\Exception $e) {
            return $this->sendError('Server Error.', ['error' => 'Something went wrong'], 500);
        }
    }
   
    
}
