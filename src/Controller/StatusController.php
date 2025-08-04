<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StatusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/status', name: 'status_check', methods: ['GET'])]
    public function statusCheck(): JsonResponse
    {
        $status = 'healthy';
        $timestamp = new \DateTimeImmutable();

        // Check database connection
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            $databaseStatus = 'connected';
        } catch (\Exception $e) {
            $databaseStatus = 'error';
            $status = 'unhealthy';
        }

        return $this->json([
            'status' => $status,
            'timestamp' => $timestamp->format('Y-m-d\TH:i:s.v\Z'),
            'service' => 'Task Checklist API',
            'version' => '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
            'checks' => [
                'database' => $databaseStatus,
                'symfony' => 'running'
            ]
        ], $status === 'healthy' ? 200 : 503);
    }
}
