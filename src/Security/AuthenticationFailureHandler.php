<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Check if the request has invalid JSON
        if ($request->getContent() && json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'error' => 'INVALID_JSON_PAYLOAD',
                'message' => 'The request body must be a valid JSON object. BBB'
            ], Response::HTTP_BAD_REQUEST);
        }

        // For other authentication failures, return a generic error
        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'error' => 'AUTHENTICATION_FAILED',
            'message' => 'Authentication failed.'
        ], Response::HTTP_UNAUTHORIZED);
    }
}
