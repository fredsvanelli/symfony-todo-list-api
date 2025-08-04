<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use App\Entity\User;

abstract class IntegrationTestCase extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected ?EntityManagerInterface $entityManager = null;
    protected ?ContainerInterface $container = null;
    protected  KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->entityManager = $this->container->get(EntityManagerInterface::class);

        // Clear database before each test
        $this->clearDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear database after each test
        $this->clearDatabase();

        $this->entityManager = null;
        $this->container = null;
    }

    protected function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();

        // Get all table names
        $tables = $connection->createSchemaManager()->listTableNames();

        // Truncate all tables with CASCADE to handle foreign keys
        foreach ($tables as $table) {
            $connection->executeStatement('TRUNCATE TABLE ' . $table . ' CASCADE');
        }
    }

    protected function createAuthenticatedClient(string $email = 'test@example.com', string $password = 'password123'): void
    {
        // First, create a user
        $this->createUser($email, $password);

        // Then login to get JWT token
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        if (isset($data['token'])) {
            $this->client->setServerParameter('HTTP_Authorization', 'Bearer ' . $data['token']);
        }
    }

    protected function createUser(string $email = 'test@example.com', string $password = 'password123'): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));
    }

    protected function assertJsonResponse(int $expectedStatusCode): void
    {
        $this->assertResponseStatusCodeSame($expectedStatusCode);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    protected function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    /**
     * Create a user using the factory
     */
    protected function createUserWithFactory(string $email = 'test@example.com', string $password = 'password123')
    {
        return \App\Tests\Factory\UserFactory::createOne([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * Create multiple users using the factory
     */
    protected function createUsersWithFactory(int $count, array $attributes = []): array
    {
        return \App\Tests\Factory\UserFactory::createMany($count, $attributes);
    }

    /**
     * Create a task using the factory
     */
    protected function createTaskWithFactory(string $title = 'Test Task', string $description = 'Test Description', bool $isDone = false, ?User $owner = null)
    {
        $attributes = [
            'title' => $title,
            'description' => $description,
            'isDone' => $isDone,
        ];

        if ($owner) {
            $attributes['owner'] = $owner;
        }

        return \App\Tests\Factory\TaskFactory::createOne($attributes);
    }

    /**
     * Create multiple tasks using the factory
     */
    protected function createTasksWithFactory(int $count, array $attributes = []): array
    {
        return \App\Tests\Factory\TaskFactory::createMany($count, $attributes);
    }
}
