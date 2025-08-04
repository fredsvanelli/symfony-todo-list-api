<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\IntegrationTestCase;

class TaskControllerTest extends IntegrationTestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $email = 'test@example.com';
        $password = 'password123';

        // Create a user using factory and get authentication token
        $userProxy = \App\Tests\Factory\UserFactory::createOne([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => $email,
            'password' => $password
        ]));

        $data = $this->getResponseData();

        $this->user = $userProxy->_real();
        $this->token = $data['token'];
    }

    public function testListTasksSuccess(): void
    {
        // Create some tasks for the user using factory
        $this->createTaskWithFactory('Task 1', 'Description 1', false, $this->user);
        $this->createTaskWithFactory('Task 2', 'Description 2', true, $this->user);

        $this->client->request('GET', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('tasks', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(2, $data['tasks']);
        $this->assertEquals(2, $data['pagination']['total_items']);
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(1, $data['pagination']['total_pages']);
    }

    public function testListTasksWithPagination(): void
    {
        // Create 15 tasks to test pagination using factory
        for ($i = 1; $i <= 15; $i++) {
            $this->createTaskWithFactory("Task $i", "Description $i", false, $this->user);
        }

        // Test first page
        $this->client->request('GET', '/api/tasks?page=1', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertCount(10, $data['tasks']); // 10 items per page
        $this->assertEquals(1, $data['pagination']['current_page']);
        $this->assertEquals(2, $data['pagination']['total_pages']);
        $this->assertTrue($data['pagination']['has_next_page']);
        $this->assertFalse($data['pagination']['has_previous_page']);

        // Test second page
        $this->client->request('GET', '/api/tasks?page=2', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertCount(5, $data['tasks']); // Remaining 5 items
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertFalse($data['pagination']['has_next_page']);
        $this->assertTrue($data['pagination']['has_previous_page']);
    }

    public function testListTasksUnauthorized(): void
    {
        $this->client->request('GET', '/api/tasks');
        $this->assertJsonResponse(401);
    }

    public function testShowTaskSuccess(): void
    {
        $task = $this->createTaskWithFactory('Test Task', 'Test Description', false, $this->user);

        $this->client->request('GET', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertEquals('Test Task', $data['title']);
        $this->assertEquals('Test Description', $data['description']);
        $this->assertFalse($data['isDone']);
    }

    public function testShowTaskNotFound(): void
    {
        $this->client->request('GET', '/api/tasks/99999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(404);
    }

    public function testShowTaskAccessDenied(): void
    {
        // Create another user and task using factory
        $otherUser = $this->createUserWithFactory('other@example.com', 'password123')->_real();
        $task = $this->createTaskWithFactory('Other Task', 'Other Description', false, $otherUser);

        $this->client->request('GET', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(403);
    }

    public function testCreateTaskSuccess(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Task',
            'description' => 'New Description',
            'isDone' => false
        ]));

        $this->assertJsonResponse(201);

        $data = $this->getResponseData();
        $this->assertEquals('New Task', $data['title']);
        $this->assertEquals('New Description', $data['description']);
        $this->assertFalse($data['isDone']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testCreateTaskWithMinimalData(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Minimal Task'
        ]));

        $this->assertJsonResponse(201);

        $data = $this->getResponseData();
        $this->assertEquals('Minimal Task', $data['title']);
        $this->assertNull($data['description']);
        $this->assertFalse($data['isDone']);
    }

    public function testCreateTaskWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid json');

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid JSON', $data['error']);
    }

    public function testCreateTaskWithEmptyTitle(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => '',
            'description' => 'Description'
        ]));

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation failed', $data['error']);
        $this->assertArrayHasKey('messages', $data);
    }

    public function testCreateTaskUnauthorized(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [
            // NO HTTP_AUTHORIZATION
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Task'
        ]));

        $this->assertJsonResponse(401);
    }

    public function testUpdateTaskSuccess(): void
    {
        $task = $this->createTaskWithFactory('Original Title', 'Original Description', false, $this->user);

        $this->client->request('PATCH', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'isDone' => true
        ]));

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertEquals('Updated Title', $data['title']);
        $this->assertEquals('Updated Description', $data['description']);
        $this->assertTrue($data['isDone']);
    }

    public function testUpdateTaskPartialData(): void
    {
        $task = $this->createTaskWithFactory('Original Title', 'Original Description', false, $this->user);

        $this->client->request('PATCH', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Title'
        ]));

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertEquals('Updated Title', $data['title']);
        $this->assertEquals('Original Description', $data['description']); // Unchanged
        $this->assertFalse($data['isDone']); // Unchanged
    }

    public function testUpdateTaskWithEmptyTitle(): void
    {
        $task = $this->createTaskWithFactory('Original Title', 'Original Description', false, $this->user);

        $this->client->request('PATCH', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => ''
        ]));

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Title cannot be empty', $data['error']);
    }

    public function testUpdateTaskWithInvalidBoolean(): void
    {
        $task = $this->createTaskWithFactory('Original Title', 'Original Description', false, $this->user);

        $this->client->request('PATCH', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'isDone' => 'not a boolean'
        ]));

        $this->assertJsonResponse(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('isDone must be a boolean value', $data['error']);
    }

    public function testUpdateTaskAccessDenied(): void
    {
        // Create another user and task using factory
        $otherUser = $this->createUserWithFactory('other@example.com', 'password123')->_real();
        $task = $this->createTaskWithFactory('Other Task', 'Other Description', false, $otherUser);

        $this->client->request('PATCH', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Title'
        ]));

        $this->assertJsonResponse(403);
    }

    public function testUpdateTaskNotFound(): void
    {
        $this->client->request('PATCH', '/api/tasks/999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Title'
        ]));

        $this->assertJsonResponse(404);
    }

    public function testDeleteTaskSuccess(): void
    {
        $task = $this->createTaskWithFactory('Task to Delete', 'Description', false, $this->user);

        $this->client->request('DELETE', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Task deleted successfully', $data['message']);

        // Verify task was actually deleted
        $this->client->request('GET', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(404);
    }

    public function testDeleteTaskAccessDenied(): void
    {
        // Create another user and task using factory
        $otherUser = $this->createUserWithFactory('other@example.com', 'password123')->_real();
        $task = $this->createTaskWithFactory('Other Task', 'Other Description', false, $otherUser);

        $this->client->request('DELETE', "/api/tasks/{$task->getId()}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(403);
    }

    public function testDeleteTaskNotFound(): void
    {
        $this->client->request('DELETE', '/api/tasks/999', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->token
        ]);

        $this->assertJsonResponse(404);
    }
}
