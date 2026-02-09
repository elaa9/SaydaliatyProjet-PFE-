<?php

namespace App\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;




class CustomerApiController extends AbstractController
{
    private $entityManager;
    private $customerRepository;
    private $serializer;
    private $validator;
    private $security;
    private $userService;

    public function __construct(
        EntityManagerInterface $entityManager, 
        CustomerRepository $customerRepository, 
        SerializerInterface $serializer, 
        ValidatorInterface $validator,
        UserService $userService,
        Security $security
        )
    {
        $this->entityManager = $entityManager;
        $this->customerRepository = $customerRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->security = $security;
        $this->userService = $userService;
    }

    
    #[Route('/api/customers', name: 'api_customers_index', methods: ['GET'])]
    public function index(): Response
    {
        // var_dump($this->isGranted('ROLE_ADMIN'), $this->security->getUser());

        // var_dump($this->userService->getUserData()->getId());
        // die();
        if($this->isGranted('ROLE_ADMIN')) {
            $customers = $this->customerRepository->findAll();

        // Check if customers exist
        if (empty($customers)) {
            return new JsonResponse(['message' => 'No customers found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($customers, 'json');

        return new JsonResponse([
            'message' => 'Customers fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK); 
        } else {
            return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);

        }
       
    }
    
    //Récuperation d'un seul client 
    #[Route('/api/customers/{id}', name: 'api_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $data = $this->serializer->serialize($customer, 'json');

            return new JsonResponse($data, 200, [], true);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    #[Route('/api/customers/add', name: 'api_customer_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('This user is not allowed to access this resource');
        }

        $data = json_decode($request->getContent(), true);
        $customer = new Customer();

        $customer->setFirstName($data['FirstName'] ?? '');
        $customer->setLastName($data['LastName'] ?? '');
        $customer->setEmail($data['Email'] ?? '');
        $customer->setPhoneNumber($data['PhoneNumber'] ?? '');
        $customer->setAddress($data['Address'] ?? '');

        $plainPassword = $data['plainPassword'] ?? '';
        $confirmPassword = $data['password'] ?? '';

        if ($plainPassword !== $confirmPassword) {
            return new JsonResponse(['error' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
        }

        $hashedPassword = $passwordHasher->hashPassword($customer, $plainPassword);
        $customer->setPassword($hashedPassword);
        $customer->setRoles($data['roles'] ?? ['ROLE_CUSTOMER']);

        $errors = $validator->validate($customer);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($customer);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Customer created successfully'], Response::HTTP_CREATED);
    }


    #[Route('/api/customers/edit/{id}', name: 'api_customer_edit', methods: ['PUT'])]
    public function edit(Request $request, Customer $customer): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);

        $customer->setFirstName($data['FirstName'] ?? '');
        $customer->setLastName($data['LastName'] ?? '');
        $customer->setEmail($data['Email'] ?? '');
        $customer->setPhoneNumber($data['PhoneNumber'] ?? '');
        $customer->setAddress($data['Address'] ?? '');
        $customer->setPassword(password_hash($data['password'] ?? '', PASSWORD_DEFAULT));
        $customer->setRoles($data['roles'] ?? ['ROLE_CUSTOMER']);

        $errors = $this->validator->validate($customer);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Customer updated successfully'], Response::HTTP_OK);

    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    //Bloquer un client
    #[Route('/api/customers/block/{id}', name: 'api_customer_block', methods: ['PUT'])]
    public function block(Customer $customer): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $customer->setBlocked(true);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Customer blocked successfully'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/customers/unblock/{id}', name: 'api_customer_unblock', methods: ['PUT'])]
    public function unblock(Customer $customer): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $customer->setBlocked(false);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Customer unblocked successfully'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    //Supprimer un client
    #[Route('/api/customers/delete/{id}', name: 'api_customer_delete', methods: ['DELETE'])]
    public function delete(Customer $customer): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->entityManager->remove($customer);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Customer deleted successfully'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    //Utilisée pour un grand nombre d'entités à insérer dans la base de données, au lieu d'insérer chaque entité individuellement
    #[Route('/api/customers/create_bulk', name: 'api_customers_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);
        $customers = [];

        foreach ($data as $customerData) {
            $customer = new Customer();
            $customer->setFirstName($customerData['FirstName'] ?? ''); 
            $customer->setLastName($customerData['LastName'] ?? ''); 
            $customer->setEmail($customerData['Email'] ?? ''); 
            $customer->setPhoneNumber($customerData['PhoneNumber'] ?? ''); 
            $customer->setAddress($customerData['Address'] ?? ''); 
            $customer->setPassword(password_hash($data['password'] ?? '', PASSWORD_DEFAULT));
            $customer->setRoles($data['roles'] ?? ['ROLE_CUSTOMER']);
        

            $errors = $this->validator->validate($customer);

            if (count($errors) > 0) {
                continue; 
            }

            $customers[] = $customer;
        }

        foreach ($customers as $customer) {
            $this->entityManager->persist($customer);
        }
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Customers created successfully'], Response::HTTP_CREATED);
    }else{
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    //Utilisée si vous devez mettre à jour un grand nombre d'entités avec la même modification
    #[Route('/api/customers/edit_bulk', name: 'api_customers_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $customerData) {
            $customer = $this->customerRepository->find($customerData['id']);

            if (!$customer) {
                continue;
            }

            $customer->setFirstName($customerData['FirstName'] ?? '');
            $customer->setLastName($customerData['LastName'] ?? '');
            $customer->setEmail($customerData['Email'] ?? '');
            $customer->setPhoneNumber($customerData['PhoneNumber'] ?? '');
            $customer->setAddress($customerData['Address'] ?? '');
            $customer->setPassword(password_hash($data['password'] ?? '', PASSWORD_DEFAULT));
            $customer->setRoles($data['roles'] ?? ['ROLE_CUSTOMER']);
    
            $errors = $this->validator->validate($customer);

            if (count($errors) > 0) {
                continue; 
            }

            $this->entityManager->persist($customer);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Customers updated successfully'], Response::HTTP_OK);
    }else{
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    //Utilisée pour bloquer un grand nombre de customer
    #[Route('/api/customers/block_bulk', name: 'api_customers_block_bulk', methods: ['PUT'])]
    public function blockBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $customerId) {
            $customer = $this->customerRepository->find($customerId);

            if (!$customer) {
                continue; 
            }

            $customer->setBlocked(true);
            $this->entityManager->persist($customer);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Customers blocked successfully'], Response::HTTP_OK);
    }else{
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    //Utilisée pour débloquer un grand nombre de customer
    #[Route('/api/customers/unblock_bulk', name: 'api_customers_unblock_bulk', methods: ['PUT'])]
    public function unblockBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $customerId) {
            $customer = $this->customerRepository->find($customerId);

            if (!$customer) {
                continue; 
            }

            $customer->setBlocked(false);
            $this->entityManager->persist($customer);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Customers unblocked successfully'], Response::HTTP_OK);
    }else{
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    //Utilisée pour supprimer un grand nombre d'entités
    #[Route('/api/customers/delete_bulk', name: 'api_customer_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);
        $deletedCustomers = [];

        foreach ($data as $customerId) {
            $customer = $this->customerRepository->find($customerId);

            if (!$customer) {
                continue; 
            }

            $this->entityManager->remove($customer);
            $deletedCustomers[] = $customer;
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Customers deleted successfully', 'deleted_customers' => $deletedCustomers], Response::HTTP_OK);
    }
    else{
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


}
