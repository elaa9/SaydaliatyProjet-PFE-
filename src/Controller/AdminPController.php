<?php

namespace App\Controller;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use App\Entity\AdminPharmacy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminPController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {

        $this->entityManager = $entityManager;
    }


    #[Route('/api/admin_pharmacy/adp/profile', name: 'adp_profile', methods: ['GET'])]
    public function AdminProfile(Security $security): JsonResponse
    {
        // if (!$this->isGranted(['ROLE_ADMIN_PHARMACY'])) {
            var_dump('$user');
        $user = $security->getUser();

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
    // }else {
    //     return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
    // }
    }



    #[Route('/api/admin_pharmacy/adp/profile/update', name: 'api_profile_update', methods: ['POST'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isGranted(['ROLE_ADMIN_PHARMACY'])) {

        // Retrieve user data from the request
        $requestData = json_decode($request->getContent(), true);
        
        // Retrieve user ID from the request data, with fallback to null if not present
        $userId = $requestData['user_id'] ?? null;
        
        // Check if $userId is null or empty, and handle the case accordingly
        if ($userId === null) {
            return new JsonResponse(['error' => 'User ID is missing'], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        // Fetch the user from the database based on user ID
        $user = $entityManager->getRepository(AdminPharmacy::class)->find($userId);
        
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        
        // Check if the requested email is already in use by another user
        $existingUserWithEmail = $entityManager->getRepository(AdminPharmacy::class)->findOneBy(['email' => $requestData['email']]);
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

    #[Route('/api/admin_pharmacy/adp/password/update', name: 'api_password_update', methods: ['POST'])]
    public function updatePassword(Request $request, EntityManagerInterface $entityManager, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage): JsonResponse
    {
        // Check if the user is authorized to access this resource
        if (!$authorizationChecker->isGranted('ROLE_ADMIN_PHARMACY')) {
            throw new AccessDeniedException('This user is not allowed to access this resource');
        }
    
        // Retrieve user ID, current password, new password, and confirmation password from the request
        $requestData = json_decode($request->getContent(), true);
        $userId = $requestData['userId'];
        $currentPassword = $requestData['password'];
        $newPassword = $requestData['newPassword'];
        $confirmPassword = $requestData['confirmPassword'];
    
        // Fetch the user from the database based on user ID
        $user = $entityManager->getRepository(AdminPharmacy::class)->find($userId);
    
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
    

}
