<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\AdminPharmacyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Controller\ApiController;
use App\Entity\Customer;
use App\Entity\Delivery;
use App\Entity\Pharmacist;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordEncoder;
    private UserRepository $userRepository;
    private ApiController $apiController;
    private AdminPharmacyRepository $adminPharmacyRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordEncoder,
        UserRepository $userRepository,
        ApiController $apiController,
        AdminPharmacyRepository $adminPharmacyRepository
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
        $this->apiController = $apiController;
        $this->adminPharmacyRepository = $adminPharmacyRepository;
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(JWTTokenManagerInterface $JWTManager, Request $request): Response
    {
        $request = $this->apiController->transformJsonBody($request);

        $user = $this->userRepository->findOneBy(['email' => $request->get('username')]);

        if (!$user) {
            $user = $this->adminPharmacyRepository->findOneBy(['Email' => $request->get('username')]);
        }

        if (!$user) {
            return $this->apiController->respondUnauthorized('User not found');
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $request->get('password'))) {
            return $this->apiController->respondUnauthorized('Invalid credentials');
        }

        $token = $JWTManager->create($user);

        return $this->json([
            'user_id'     => $user->getId(),
            'email'       => $user->getUserIdentifier(),
            'first_Name'  => $user->getFirstName(),
            'last_Name'   => $user->getLastName(),
            'roles'       => $user->getRoles(),
            'token'       => $token,
        ]);
    }
    
    #[Route('/login/customer', name: 'api_login_customer', methods: ['POST'])]
    public function loginCustomer(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        $email = $requestData['email'];
        $password = $requestData['password'];

        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['Email' => $email]);

        // Check if the customer account is blocked
        if ($customer && $customer->isBlocked()) {
            return new JsonResponse(['message' => 'Your account is blocked'], Response::HTTP_FORBIDDEN);
        }

        if (!$customer) {
            return new JsonResponse(['message' => 'Email not found'], Response::HTTP_NOT_FOUND);
        }

        // Check password (you can use Symfony's built-in password encoder here)
        if (!password_verify($password, $customer->getPassword())) {
            return new JsonResponse(['message' => 'Incorrect password'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Generate JWT token for the customer
            $token = $JWTManager->create($customer);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Failed to create JWT token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Prepare response with customer data and token
        $responseData = [
            'customer' => [
                'id' => $customer->getId(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'email' => $customer->getEmail(),
            ],
            'token' => $token,
        ];

        return new JsonResponse($responseData);
    }

    /**
     * @Route("/api/login/delivery", name="api_login_delivery", methods={"POST"})
     */
    public function loginDelivery(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        $email = $requestData['email'];
        $password = $requestData['password'];

        $delivery = $this->entityManager->getRepository(Delivery::class)->findOneBy(['Email' => $email]);

        // Check if the delivery account is blocked (assuming the Delivery entity has an isBlocked() method)
        if ($delivery && $delivery->isBlocked()) {
            return new JsonResponse(['message' => 'Your account is blocked'], Response::HTTP_FORBIDDEN);
        }

        if (!$delivery) {
            return new JsonResponse(['message' => 'Email not found'], Response::HTTP_NOT_FOUND);
        }

        // Check password (you can use Symfony's built-in password encoder here)
        if (!password_verify($password, $delivery->getPassword())) {
            return new JsonResponse(['message' => 'Incorrect password'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Generate JWT token for the delivery
            $token = $JWTManager->create($delivery);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Failed to create JWT token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Prepare response with delivery data and token
        $responseData = [
            'delivery' => [
                'id' => $delivery->getId(),
                'firstName' => $delivery->getFirstName(),
                'lastName' => $delivery->getLastName(),
                'email' => $delivery->getEmail(),
            ],
            'token' => $token,
        ];

        return new JsonResponse($responseData);
    }

    /**
     * @Route("/api/login/pharmacist", name="api_login_pharmacist", methods={"POST"})
     */
    public function loginPharmacist(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        $email = $requestData['email'];
        $password = $requestData['password'];

        $pharmacist = $this->entityManager->getRepository(Pharmacist::class)->findOneBy(['Email' => $email]);

        if (!$pharmacist) {
            return new JsonResponse(['message' => 'Email not found'], Response::HTTP_NOT_FOUND);
        }

        // Check password (you can use Symfony's built-in password encoder here)
        if (!password_verify($password, $pharmacist->getPassword())) {
            return new JsonResponse(['message' => 'Incorrect password'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Generate JWT token for the pharmacist
            $token = $JWTManager->create($pharmacist);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Failed to create JWT token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Prepare response with pharmacist data and token
        $responseData = [
            'pharmacist' => [
                'id' => $pharmacist->getId(),
                'firstName' => $pharmacist->getFirstName(),
                'lastName' => $pharmacist->getLastName(),
                'email' => $pharmacist->getEmail(),
                'roles' => $pharmacist->getRoles(),
            ],
            'token' => $token,
        ];

        return new JsonResponse($responseData);
    }

    // /**
    //  * @Route("/api/login/adminPharmacy", name="api_login_admin_pharmacy", methods={"POST"})
    //  */
    // public function loginAdminPharmacy(Request $request, JWTTokenManagerInterface $JWTManager): JsonResponse
    // {
    //     $requestData = json_decode($request->getContent(), true);

    //     $email = $requestData['email'];
    //     $password = $requestData['password'];

    //     $adminPharmacy = $this->entityManager->getRepository(AdminPharmacy::class)->findOneBy(['Email' => $email]);

    //     if (!$adminPharmacy) {
    //         return new JsonResponse(['message' => 'Email not found'], Response::HTTP_NOT_FOUND);
    //     }

    //     // Check password (you can use Symfony's built-in password encoder here)
    //     if (!password_verify($password, $adminPharmacy->getPassword())) {
    //         return new JsonResponse(['message' => 'Incorrect password'], Response::HTTP_UNAUTHORIZED);
    //     }

    //     try {
    //         // Generate JWT token for the admin pharmacy
    //         $token = $JWTManager->create($adminPharmacy);
    //     } catch (\Exception $e) {
    //         return new JsonResponse(['message' => 'Failed to create JWT token'], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }

    //     // Prepare response with admin pharmacy data and token
    //     $responseData = [
    //         'adminPharmacy' => [
    //             'id' => $adminPharmacy->getId(),
    //             'firstName' => $adminPharmacy->getFirstName(),
    //             'lastName' => $adminPharmacy->getLastName(),
    //             'email' => $adminPharmacy->getEmail(),
    //             'roles' => $adminPharmacy->getRoles(),
    //         ],
    //         'token' => $token,
    //     ];

    //     return new JsonResponse($responseData);
    // }
}
