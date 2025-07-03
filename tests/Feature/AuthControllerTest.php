<?php

namespace Tests\Feature;

use App\Models\Auth\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

use Spatie\Permission\Contracts\Role as RoleContract;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    protected function setUp(): void
    {
        parent::setUp();
    
        $this->app->bind(\Spatie\Permission\Contracts\Role::class, \Spatie\Permission\Models\Role::class);
    }
    
    #[Test]
    public function it_logs_in_user_with_valid_portal_credentials()
    {
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'company_representative', 'guard_name' => 'api']);
        
        // Arrange: prepare a fake portal response
        Http::fake([
            env('PORTAL_API_URL') . '/api/auth/login' => Http::response([
                'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.' .
                   'eyJzdWIiOiIxIiwibmFtZSI6IkpvaG4gRG9lIiwicm9sZSI6InN0dWRlbnQifQ.' .
                   'SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
                'data' => [
                    'id' => 999,
                    'email' => 'user@example.com',
                    'role' => 'student',
                    'first_name' => 'John',
                    'last_name' => 'Doe'
                ],
            ], 200),
        ]);

        // Act: send login request to your app
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert: check if login was successful and user is created
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user',
                         'access_token',
                         'refresh_token',
                         'portal_token',
                         'profile_complete',
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'portal_user_id' => 999,
            'first_name' => 'John',
        ]);
        
        dump($response->json());
$response->assertStatus(200);
    }

    #[Test]
    public function it_fails_when_portal_returns_invalid_credentials()
    {
        // Arrange: fake a failed response from the portal
        Http::fake([
            env('PORTAL_API_URL') . '/api/auth/login' => Http::response([
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid credentials',
                 ]);
    }
    
}
