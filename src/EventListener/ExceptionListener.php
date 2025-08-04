<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle entity not found exceptions
        if ($exception instanceof NotFoundHttpException) {
            $response = new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'error' => 'NOT_FOUND',
                'message' => $exception->getMessage()
            ], Response::HTTP_NOT_FOUND);

            $event->setResponse($response);
        }
    }
}
