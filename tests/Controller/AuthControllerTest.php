<?php

namespace App\Tests\Controller;

use App\Tests\IntegrationTestCase;

class AuthControllerTest extends IntegrationTestCase
{
    public function testUserRegistrationSuccess(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $this->assertJsonResponse(201);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertEquals('test@example.com', $data['email']);
    }

    public function testUserRegistrationWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'email' => 'invalid-email',
            'password' => 'password123',
        ]));

        $this->assertJsonResponse(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('VALIDATION_FAILED', $data['error']);
        $this->assertArrayHasKey('messages', $data);
    }

    public function testUserRegistrationWithShortPassword(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => '123',
        ]));

        $this->assertJsonResponse(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('VALIDATION_FAILED', $data['error']);
        $this->assertArrayHasKey('messages', $data);
    }

    public function testUserRegistrationWithDuplicateEmail(): void
    {
        // First, create a user using factory
        $this->createUserWithFactory('test@example.com', 'password123');

        // Second registration with same email
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password456',
        ]));

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('EMAIL_ALREADY_EXISTS', $data['error']);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Email already exists.', $data['message']);
    }

    public function testUserLoginSuccess(): void
    {
        // First, create a user using factory
        $this->createUserWithFactory('test@example.com', 'password123');

        // Then try to login
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]));

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();

        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);

        // Verify JWT token format (should have 3 parts separated by dots)
        $tokenParts = explode('.', $data['token']);
        $this->assertCount(3, $tokenParts);
    }

    public function testUserLoginWithInvalidCredentials(): void
    {
        // First, create a user using factory
        $this->createUserWithFactory('test@example.com', 'password123');

        // Then try to login with wrong password
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]));

        $this->assertJsonResponse(401);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('The presented password is invalid.', $data['message']);
    }

    public function testUserLoginWithNonExistentUser(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]));

        $this->assertJsonResponse(401);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Bad credentials.', $data['message']);
    }

    public function testUserLoginMissingFields(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => 'test@example.com',
        ]));

        $this->assertJsonResponse(401);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('UNAUTHORIZED', $data['error']);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals("'email' and 'password' are required.", $data['message']);
    }

    public function testUserLoginWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [], 'invalid json');

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('INVALID_JSON', $data['error']);
    }

    public function testUserRegistrationWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], 'invalid json');

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('INVALID_JSON', $data['error']);
    }
}
