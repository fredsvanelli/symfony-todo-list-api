<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ErrorResponse
{
    public static function validationError(
        ConstraintViolationListInterface $errors,
        int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY,
        string $errorCode = 'VALIDATION_FAILED'
    ): JsonResponse {
        $errorMessages = [];

        foreach ($errors as $error) {
            $errorMessages[] = [
                'property' => $error->getPropertyPath(),
                'message' => $error->getMessage()
            ];
        }

        return new JsonResponse([
            'statusCode' => $statusCode,
            'error' => $errorCode,
            'messages' => $errorMessages
        ], $statusCode);
    }

    public static function invalidJson(): JsonResponse
    {
        return new JsonResponse([
            'statusCode' => Response::HTTP_BAD_REQUEST,
            'error' => 'INVALID_JSON',
            'message' => 'The request content is not a valid JSON.'
        ], Response::HTTP_BAD_REQUEST);
    }

    public static function accessDenied(): JsonResponse
    {
        return new JsonResponse([
            'statusCode' => Response::HTTP_FORBIDDEN,
            'error' => 'ACCESS_DENIED',
            'message' => 'You are not allowed to access this resource.'
        ], Response::HTTP_FORBIDDEN);
    }

    public static function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse([
            'statusCode' => Response::HTTP_UNAUTHORIZED,
            'error' => 'UNAUTHORIZED',
            'message' => $message
        ], Response::HTTP_UNAUTHORIZED);
    }

    public static function customError(
        string $errorCode,
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return new JsonResponse([
            'statusCode' => $statusCode,
            'error' => $errorCode,
            'message' => $message
        ], $statusCode);
    }
}
