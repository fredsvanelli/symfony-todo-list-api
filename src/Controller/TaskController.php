<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskRepository $taskRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Get pagination parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10; // 10 tasks per page
        $offset = ($page - 1) * $limit;

        // Get total count of user's tasks
        $totalTasks = $this->taskRepository->count(['owner' => $user]);

        // Get paginated tasks
        $tasks = $this->taskRepository->findBy(
            ['owner' => $user],
            ['id' => 'DESC'],
            $limit,
            $offset
        );

        // Calculate pagination info
        $totalPages = ceil($totalTasks / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPreviousPage = $page > 1;

        return $this->json([
            'tasks' => $tasks,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalTasks,
                'items_per_page' => $limit,
                'has_next_page' => $hasNextPage,
                'has_previous_page' => $hasPreviousPage,
                'next_page' => $hasNextPage ? $page + 1 : null,
                'previous_page' => $hasPreviousPage ? $page - 1 : null
            ]
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($task->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($task);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $task = new Task();
        $task->setTitle($data['title'] ?? '');
        $task->setDescription($data['description'] ?? null);
        $task->setIsDone($data['isDone'] ?? false);
        $task->setOwner($user);

        // Validate the task
        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation failed',
                'messages' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json($task, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(Task $task, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($task->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Validate input data
        if (isset($data['title'])) {
            if (empty(trim($data['title']))) {
                return $this->json(['error' => 'Title cannot be empty'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($data['title']) > 255) {
                return $this->json(['error' => 'Title cannot be longer than 255 characters'], Response::HTTP_BAD_REQUEST);
            }
            $task->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            if (strlen($data['description']) > 1000) {
                return $this->json(['error' => 'Description cannot be longer than 1000 characters'], Response::HTTP_BAD_REQUEST);
            }
            $task->setDescription($data['description']);
        }

        if (isset($data['isDone'])) {
            if (!is_bool($data['isDone'])) {
                return $this->json(['error' => 'isDone must be a boolean value'], Response::HTTP_BAD_REQUEST);
            }
            $task->setIsDone($data['isDone']);
        }

        // Validate the updated task
        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => 'Validation failed',
                'messages' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($task);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($task->getOwner() !== $user) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(['message' => 'Task deleted successfully']);
    }
}
