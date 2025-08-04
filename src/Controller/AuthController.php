<?php

namespace App\Controller;

use App\Entity\User;
use App\Response\ErrorResponse;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (Exception) {
            return ErrorResponse::invalidJson();
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setPassword($data['password'] ?? '');

        // Validate the user
        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            return ErrorResponse::validationError($errors);
        }

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            return ErrorResponse::customError('EMAIL_ALREADY_EXISTS', 'Email already exists.');
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail()
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login()
    {
        // Processed by src/Security/JsonLoginAuthenticator.php@authenticate()
    }
}
