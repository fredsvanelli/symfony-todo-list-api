<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Response\ErrorResponse;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
        if ($task->getOwner() !== $this->getUser()) {
            return ErrorResponse::accessDenied();
        }

        return $this->json($task);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $data = $request->toArray();
        } catch (Exception) {
            return ErrorResponse::invalidJson();
        }

        $task = new Task();
        $task->setTitle($data['title'] ?? '');
        $task->setDescription($data['description'] ?? null);
        $task->setIsDone($data['isDone'] ?? false);
        $task->setOwner($user);

        // Validate the task
        $errors = $this->validator->validate($task);

        if (count($errors) > 0) {
            return ErrorResponse::validationError($errors);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json($task, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(Task $task, Request $request): JsonResponse
    {
        if ($task->getOwner() !== $this->getUser()) {
            return ErrorResponse::accessDenied();
        }

        try {
            $data = $request->toArray();
        } catch (Exception) {
            return ErrorResponse::invalidJson();
        }

        $task->setTitle($data['title'] ?? $task->getTitle());
        $task->setDescription($data['description'] ?? $task->getDescription());
        $task->setIsDone($data['isDone'] ?? $task->getIsDone());

        // Validate the updated task
        $errors = $this->validator->validate($task);

        if (count($errors) > 0) {
            return ErrorResponse::validationError($errors);
        }

        $this->entityManager->flush();

        return $this->json($task);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        if ($task->getOwner() !== $this->getUser()) {
            return ErrorResponse::accessDenied();
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(['message' => 'Task deleted successfully']);
    }
}
