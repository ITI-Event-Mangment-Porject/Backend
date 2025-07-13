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
use App\Models\Auth\Track;
use App\Models\JobFair\JobFairParticipation; // Import JobFairParticipation model
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
            $portalResponse = $this->authenticateWithPortal($email, $password);
            // dd($portalResponse);
            
            if (!$portalResponse['success']) {
                return $this->sendError($portalResponse['message'], [], 401);
            }
            
            $userData = $portalResponse['data'];
            // $userData = $portalData['user'];
            
            
            // Validate required fields from portal response
            if (!isset($userData['id'], $userData['role'], $userData['email']) && ( !isset($userData['first_name'], $userData['last_name']) || !isset($userData['full_name']) )) {
                return $this->sendError('Invalid user data from portal', [], 400);
            }  
            
            if (!is_string($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->sendError('Invalid email format.');
            }
            $role = $userData['role'] === 'alumni' ? 'student' : $userData['role']; 
            $role = $userData['role'] === 'company' ? 'company_representative' : $userData['role']; 
            // Check if user exists in local database
            $user = User::where('portal_user_id', $userData['id'])->first();
            
            if (!$user) {
                // Create new user
                
                $track = Track::find($userData['track'] ?? null);
                $user = User::create([
                    'portal_user_id' => $userData['id'],
                    'email' => $userData['email'],
                    // 'cv_path' => $userData['cv_path'] ?? null,
                    'first_name' => $userData['first_name'] ?? $userData['full_name'] ?? $userData['company_name'],
                    'last_name' => $userData['last_name'] ?? $userData['full_name'] ?? $userData['company_name'],
                    'phone' => $userData['phone'] ?? null,
                    'track_id' => $track->id ?? null,
                    
                    'intake' => $userData['intake'] ?? null,
                    'graduation_year' => $userData['graduation_year'] ?? null,
                    'bio' => $userData['summary'] ?? null,
                    'linkedin_url' => $userData['linkedin'] ?? null,
                    'github_url' => $userData['github'] ?? null,
                    'portfolio_url' => $userData['portfolio'] ?? null,
                    'profile_image' => $userData['profile_picture'] ?? null,
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
                    'profile_complete' => false,
                    'role' => $user->getRoleNames()->first(),
                ], 'User created. Profile completion required.');
            }
            
            
            // Generate local tokens
            $accessToken = JWTAuth::fromUser($user);
            $refreshToken = JWTAuth::customClaims([
                'type' => 'refresh',
                'exp' => now()->addDays(30)->timestamp  // 30 days expiry
            ])->fromUser($user);
            
            // Check profile completion
            // Check profile completion
            $profileComplete = $this->checkProfileComplete($user);

            $responseData = [
                'user' => $user,
                'role' => $user->getRoleNames()->first(),
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'profile_complete' => $profileComplete,
            ];

            // If the user is a company_representative, fetch the most recent job fair participation details
            if ($user->hasRole('company_representative')) {
                $mostRecentParticipation = JobFairParticipation::where('submitted_by', $user->id)
                    ->latest() // Order by latest created_at
                    ->select('id', 'company_id') // Select only necessary fields
                    ->first(); // Get only the first (most recent) record

                if ($mostRecentParticipation) {
                    $responseData['user']['job_fair_participation_id'] = $mostRecentParticipation->id;
                    $responseData['user']['company_id'] = $mostRecentParticipation->company_id;
                } else {
                    $responseData['user']['job_fair_participation_id'] = null;
                    $responseData['user']['company_id'] = null;
                }
            }
            
            return $this->sendResponse($responseData, 'Login successful');
            
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
            return $this->sendError('Login failed', ['error'=>$e->getMessage()], 500);
        }
    }
    
    private function authenticateWithPortal($email, $password)
    {
        try {
            $response = $this->httpClient->post(env('PORTAL_API_URL') . '/api/auth/external-login', [
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
            
           // Check for a 200 status and the success flag from the portal's response
            if ($statusCode === 200 && isset($body['success']) && $body['success'] === true && isset($body['data'])) {
                // The login method expects the user data, not the whole body.
                // So we should return the nested 'data' object.
                return [
                    'success' => true,
                    'message' => $body['message'],
                    'data'    => $body['data'] // Return the actual user data object
                ];
            }
    
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Authentication failed'
            ];
            
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $body = json_decode($response->getBody()->getContents(), true);
                return [
                    'success' => false,
                    'message' => $body['message'] ?? 'Invalid credentials'
                ];
            }
            return [
                'success' => false,
                'message' => 'Connection to portal failed'
            ];
        } catch (ConnectException $e) {
            return [
                'success' => false,
                'message' => 'Unable to connect to authentication server'
            ];
        } catch (\Exception $e) {
            Log::error('Portal authentication error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Authentication failed due to an unexpected error'
            ];
        }
    }
    
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
