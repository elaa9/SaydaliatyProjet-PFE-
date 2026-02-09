<?php

namespace App\Controller;

use App\Entity\AdminPharmacy;
use App\Entity\Pharmacy;
use App\Repository\AdminPharmacyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AdminPharmacyApiController extends AbstractController
{
    private $entityManager;
    private $adminPharmacyRepository;
    private $serializer;
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdminPharmacyRepository $adminPharmacyRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->adminPharmacyRepository = $adminPharmacyRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/api/adminPharmacies', name: 'app_admin_pharmacy_api')]
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {
        $adminPharmacies = $this->adminPharmacyRepository->findAll();
        if (empty($adminPharmacies)) {
            return new JsonResponse(['message' => 'No admin pharmacies found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($adminPharmacies, 'json');

        return new JsonResponse([
            'message' => 'Admin pharmacies fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/adminPharmacies/{id}', name: 'api_admin_pharmacy_show', methods: ['GET'])]
    public function show(AdminPharmacy $adminPharmacy): Response
    {
        var_dump('$adminPharmacy', $adminPharmacy);
        // if($this->isGranted('ROLE_ADMIN')) {
        $data = $this->serializer->serialize($adminPharmacy, 'json');

        return new JsonResponse($data, 200, [], true);
    // }else {
    //     return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
    //     }
    }  


    #[Route('/api/adminPharmacies/add', name: 'api_admin_pharmacy_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $data = json_decode($request->getContent(), true);

            $adminPharmacy = new AdminPharmacy();

            $adminPharmacy->setFirstName($data['FirstName'] ?? ''); 
            $adminPharmacy->setLastName($data['LastName'] ?? ''); 
            $adminPharmacy->setEmail($data['Email'] ?? '');
            $plainPassword = $data['plainPassword'] ?? '';
            $adminPharmacy->setPlainPassword($plainPassword);
            
            $password = $data['password'] ?? '';
            $adminPharmacy->setPassword(password_hash($password, PASSWORD_DEFAULT)); 

            // Vérifier que les mots de passe correspondent
            if ($plainPassword !== $password) {
                return new JsonResponse(['error' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
            }

            $adminPharmacy->setRoles($data['roles'] ?? ['ROLE_ADMIN_PHARMACY']);  

            $pharmacyId = $data['Pharmacy'] ?? null;
            if ($pharmacyId) {
                $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
                if (!$pharmacy) {
                    return new JsonResponse(['error' => 'Pharmacy not found'], Response::HTTP_NOT_FOUND);
                }
                $adminPharmacy->setPharmacy($pharmacy);
            } else {
                return new JsonResponse(['error' => 'Pharmacy ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $errors = $this->validator->validate($adminPharmacy);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->entityManager->persist($adminPharmacy);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Admin Pharmacy created successfully'], Response::HTTP_CREATED);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    #[Route('/api/adminPharmacies/edit/{id}', name: 'api_admin_pharmacy_edit', methods: ['PUT'])]
    public function edit(Request $request, AdminPharmacy $adminPharmacy): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $data = json_decode($request->getContent(), true);

            // Log the request data
            error_log("Edit Request Data: " . print_r($data, true));

            $adminPharmacy->setFirstName($data['FirstName'] ?? $adminPharmacy->getFirstName());
            $adminPharmacy->setLastName($data['LastName'] ?? $adminPharmacy->getLastName());
            $adminPharmacy->setEmail($data['Email'] ?? $adminPharmacy->getEmail());
            $adminPharmacy->setPassword(password_hash($data['password'] ?? '', PASSWORD_DEFAULT)); 
            $adminPharmacy->setRoles($data['roles'] ?? ['ROLE_ADMIN_PHARMACY']);   

            $pharmacyId = $data['Pharmacy'] ?? null;
            if ($pharmacyId) {
                $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
                if (!$pharmacy) {
                    error_log("Pharmacy not found: " . $pharmacyId);
                    return new JsonResponse(['error' => 'Pharmacy not found'], Response::HTTP_NOT_FOUND);
                }
                $adminPharmacy->setPharmacy($pharmacy);
            }

            $errors = $this->validator->validate($adminPharmacy);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                error_log("Validation Errors: " . print_r($errorMessages, true));
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->entityManager->flush();
            error_log("Admin Pharmacy updated successfully.");

            return new JsonResponse(['message' => 'Admin Pharmacy updated successfully'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/adminPharmacies/delete/{id}', name: 'api_admin_pharmacy_delete', methods: ['DELETE'])]
    public function delete(AdminPharmacy $adminPharmacy): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->entityManager->remove($adminPharmacy);
            $this->entityManager->flush();

            error_log("Admin Pharmacy deleted successfully.");

            return new JsonResponse(['message' => 'Admin Pharmacy deleted successfully'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    #[Route('/api/adminPharmacies/create_bulk', name: 'api_admin_pharmacy_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);
        $adminPharmacies = [];

        foreach ($data as $adminPharmacyData) {
            $adminPharmacy = new AdminPharmacy();
            $adminPharmacy->setFirstName($adminPharmacyData['FirstName'] ?? ''); 
            $adminPharmacy->setLastName($adminPharmacyData['LastName'] ?? ''); 
            $adminPharmacy->setEmail($adminPharmacyData['Email'] ?? '');
            $adminPharmacy->setPassword(password_hash($adminPharmacyData['password'] ?? '', PASSWORD_DEFAULT)); 
            $adminPharmacy->setRoles($adminPharmacyData['roles'] ?? ['ROLE_ADMIN_PHARMACY']); 

            if (isset($adminPharmacyData['pharmacyId'])) {
                $pharmacyId = $adminPharmacyData['pharmacyId'];

                $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);

                if (!$pharmacy) {
                    $errors[] = 'Pharmacy not found for admin pharmacy ' . $adminPharmacy->getFirstName() . ' ' . $adminPharmacy->getLastName();
                    continue;
                }

                $adminPharmacy->setPharmacy($pharmacy);
            }

            $errors = $this->validator->validate($adminPharmacy);

            if (count($errors) > 0) {
                continue; 
            }

            $adminPharmacies[] = $adminPharmacy;
        }

        foreach ($adminPharmacies as $adminPharmacy) {
            $this->entityManager->persist($adminPharmacy);
        }
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Admin Pharmacies created successfully'], Response::HTTP_CREATED);
    }
        else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/adminPharmacies/edit_bulk', name: 'api_admin_pharmacy_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $adminPharmacyData) {

            if (!isset($adminPharmacyData['id'])) {
                continue;
            }

            $adminPharmacy = $this->adminPharmacyRepository->find($adminPharmacyData['id']);

            if (!$adminPharmacy) {
                continue;
            }

            $adminPharmacy->setFirstName($adminPharmacyData['FirstName'] ?? $adminPharmacy->getFirstName());
            $adminPharmacy->setLastName($adminPharmacyData['LastName'] ?? $adminPharmacy->getLastName());
            $adminPharmacy->setEmail($adminPharmacyData['Email'] ?? $adminPharmacy->getEmail());
            $adminPharmacy->setPassword(password_hash($adminPharmacyData['password'] ?? '', PASSWORD_DEFAULT)); 
            $adminPharmacy->setRoles($adminPharmacyData['roles'] ?? ['ROLE_ADMIN_PHARMACY']); 

            if (isset($adminPharmacyData['pharmacyId'])) {
                $pharmacyId = $adminPharmacyData['pharmacyId'];
                $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
                
                if ($pharmacy) {
                    $adminPharmacy->setPharmacy($pharmacy);
                }
            }

            $errors = $this->validator->validate($adminPharmacy);

            if (count($errors) > 0) {
                continue;
            }

            $this->entityManager->flush();
        }

        return new JsonResponse(['message' => 'Admin Pharmacies updated successfully'], Response::HTTP_OK);
        }
        else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/adminPharmacies/delete_bulk', name: 'api_admin_pharmacy_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $adminPharmacyId) {
            $adminPharmacy = $this->entityManager->getRepository(AdminPharmacy::class)->find($adminPharmacyId);

            if (!$adminPharmacy) {
                continue;
            }

            $this->entityManager->remove($adminPharmacy);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Admin Pharmacies deleted successfully'], Response::HTTP_OK);
    }
        else {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }





    #[Route('/api/adminn_pharmacy/adpp/profile', name: 'adpp_profile', methods: ['GET'])]
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
}
