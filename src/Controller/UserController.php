<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;




class UserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'user_index', methods: ['GET'])]
    public function index(SerializerInterface $serializer): Response
    {

        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        // Serialize users to JSON
        $usersJson = $serializer->serialize($users, 'json');

        return new Response($usersJson, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/users/{id}', name: 'user_show', methods: ['GET'])]
    public function show(int $id, SerializerInterface $serializer): Response
    {
        if (!$this->isGranted(['ROLE_ADMIN', 'ROLE_ADMIN_PHARMACY'])) {

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Serialize user to JSON
        $userJson = $serializer->serialize($user, 'json');

        return new JsonResponse($userJson, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
    }
    }

    #[Route('/api/user/profile', name: 'user_profile', methods: ['GET'])]
    public function userProfile(Security $security): JsonResponse
    {

        if (!$this->isGranted(['ROLE_ADMIN', 'ROLE_ADMIN_PHARMACY'])) {
            // var_dump( '$user');

        $user = $security->getUser();
        // var_dump( $user);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Customize this according to your user entity structure
        $userData = [
            'user_id' => $user->getId(),
             'email'  => $user->getUserIdentifier(),
             'first_Name' => $user->getFirstName(),
             'last_Name' => $user->getLastName(),
             'roles' =>$user->getRoles()
        ];

        return new JsonResponse($userData, JsonResponse::HTTP_OK);
  }else {
     return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
   }
    }



    #[Route('/api/profile/update', name: 'api_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted(['ROLE_ADMIN', 'ROLE_ADMIN_PHARMACY'])) {

        // Retrieve user data from the request
        $requestData = json_decode($request->getContent(), true);
        
        // Retrieve user ID from the request data, with fallback to null if not present
        $userId = $requestData['user_id'] ?? null;
        
        // Check if $userId is null or empty, and handle the case accordingly
        if ($userId === null) {
            return new JsonResponse(['error' => 'User ID is missing'], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        // Fetch the user from the database based on user ID
        $user = $entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        // Check if the requested email is already in use by another user
        $existingUserWithEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $requestData['email']]);
        if ($existingUserWithEmail && $existingUserWithEmail->getId() !== $userId) {
            return new JsonResponse(['error' => 'Email address already in use'], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        // Update user profile data
        $user->setFirstName($requestData['first_Name'] ?? $user->getFirstName());
        $user->setLastName($requestData['last_Name'] ?? $user->getLastName());
        $user->setEmail($requestData['email'] ?? $user->getEmail());
        // Add other fields as needed
        
        // Persist changes to the database
        $entityManager->flush();
        
        return new JsonResponse(['message' => 'Profile updated successfully'], JsonResponse::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
    }
    }

    #[Route('/api/password/update', name: 'api_password_update', methods: ['POST'])]
    public function updatePassword(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage): JsonResponse
    {
        // Check if the user is authorized to access this resource
        if (!$authorizationChecker->isGranted('ROLE_ADMIN') && !$authorizationChecker->isGranted('ROLE_ADMIN_PHARMACY')) {
            throw new AccessDeniedException('This user is not allowed to access this resource');
        }
    
        // Retrieve user ID, current password, new password, and confirmation password from the request
        $requestData = json_decode($request->getContent(), true);
        $userId = $requestData['userId'];
        $currentPassword = $requestData['password'];
        $newPassword = $requestData['newPassword'];
        $confirmPassword = $requestData['confirmPassword'];
    
        // Fetch the user from the database based on user ID
        $user = $entityManager->getRepository(User::class)->find($userId);
    
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
    
        // Verify if the current password matches the user's actual password
        if (!password_verify($currentPassword, $user->getPassword())) {
            return new JsonResponse(['error' => 'Current password is incorrect'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Verify if the new password matches the confirmation password
        if ($newPassword !== $confirmPassword) {
            return new JsonResponse(['error' => 'New password and confirmation password do not match'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
        // Update user's password
        $user->setPassword($hashedPassword);
    
        // Invalidate existing session by regenerating session ID
    
        // Persist changes to the database
        $entityManager->flush();
    
        return new JsonResponse(['message' => 'Password updated successfully'], JsonResponse::HTTP_OK);
    }
    
    #[Route('/users/create', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Decode the JSON request body
        $requestData = json_decode($request->getContent(), true);

        // Extract plaintext password from request data
        $plaintextPassword = $requestData['password'];

        // Create a new User instance
        $user = new User();

        // Set user properties from request data
        $user->setEmail($requestData['email']);
        $user->setFirstName($requestData['firstName']); 
        $user->setLastName($requestData['lastName']);  

        // Set the plain password before hashing and setting the password
        $user->setPlainPassword($plaintextPassword);

        // Hash the plaintext password and set it on the user
        $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
        $user->setPassword($hashedPassword);

        // Set default role as ROLE_USER
        $user->setRoles(['ROLE_ADMIN']);

        // Validate the user entity
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            // Return validation errors if any
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Save the user to the database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Return success response
        return new JsonResponse(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }
}
