<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class JsonLoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/api/auth/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        // Check if the request has invalid JSON
        if ($request->getContent() && json_last_error() !== JSON_ERROR_NONE) {
            throw new CustomUserMessageAuthenticationException('Invalid JSON');
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            throw new CustomUserMessageAuthenticationException('Invalid JSON');
        }

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new CustomUserMessageAuthenticationException('Email and password are required');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();
        $jwt = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $jwt]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof CustomUserMessageAuthenticationException && $exception->getMessage() === 'Invalid JSON') {
            return new JsonResponse([
                'code' => Response::HTTP_BAD_REQUEST,
                'error' => 'INVALID_JSON_PAYLOAD',
                'message' => 'The request body must be a valid JSON object.'
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'error' => 'AUTHENTICATION_FAILED',
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'code' => Response::HTTP_UNAUTHORIZED,
            'error' => 'AUTHENTICATION_REQUIRED',
            'message' => $authException?->getMessage() ?? 'Authentication required.'
        ], Response::HTTP_UNAUTHORIZED);
    }
}
