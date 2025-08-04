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
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('User registered successfully', $data['message']);
        $this->assertEquals('test@example.com', $data['user']['email']);
        $this->assertArrayHasKey('id', $data['user']);
    }

    public function testUserRegistrationWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'email' => 'invalid-email',
            'password' => 'password123',
        ]));

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertEquals(400, $data['code']);
        $this->assertEquals('VALIDATION_FAILED', $data['error']);
    }

    public function testUserRegistrationWithShortPassword(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'email' => 'test@example.com',
            'password' => '123',
        ]));

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertEquals(400, $data['code']);
        $this->assertEquals('VALIDATION_FAILED', $data['error']);
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
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('messages', $data);
        $this->assertEquals(400, $data['code']);
        $this->assertEquals('VALIDATION_FAILED', $data['error']);
        $this->assertEquals('Email already exists', $data['messages'][0]);
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

    public function testUserLoginWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('The request body must be a valid JSON object.', $data['message']);
    }

    public function testUserRegistrationWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], 'invalid json');

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals(400, $data['code']);
        $this->assertEquals('INVALID_JSON_PAYLOAD', $data['error']);
        $this->assertEquals('The request body must be a valid JSON object.', $data['message']);
    }
}
