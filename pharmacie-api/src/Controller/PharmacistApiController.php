<?php

namespace App\Controller;

use App\Entity\Pharmacist;
use App\Entity\Pharmacy;
use App\Repository\PharmacistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PharmacistApiController extends AbstractController
{

    private $entityManager;
    private $pharmacistsRepository;
    private $serializer;
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PharmacistRepository $pharmacistsRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->pharmacistsRepository = $pharmacistsRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/api/pharmacists', name: 'app_pharmacists_api')]
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {
        $pharmacists = $this->pharmacistsRepository->findAll();
        if (empty($pharmacists)) {
            return new JsonResponse(['message' => 'No pharmacists found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($pharmacists, 'json');

        return new JsonResponse([
            'message' => 'Pharmacists fetched successfully',
            'data' => json_decode($data, true)
        ], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/pharmacists/{id}', name: 'api_pharmacists_show', methods: ['GET'])]
    public function show(Pharmacist $pharmacist): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = $this->serializer->serialize($pharmacist, 'json');

        return new JsonResponse($data, 200, [], true);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/pharmacists/add', name: 'api_pharmacists_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        $pharmacist = new Pharmacist();

        $pharmacist->setFirstName($data['FirstName'] ?? ''); 
        $pharmacist->setLastName($data['LastName'] ?? ''); 
        $pharmacist->setEmail($data['Email'] ?? '');
        $plainPassword = $data['plainPassword'] ?? '';
            $pharmacist->setPlainPassword($plainPassword);
            
            $password = $data['password'] ?? '';
            $pharmacist->setPassword(password_hash($password, PASSWORD_DEFAULT)); 

            // Vérifier que les mots de passe correspondent
            if ($plainPassword !== $password) {
                return new JsonResponse(['error' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
            }
        $pharmacist->setRoles($data['roles'] ?? ['ROLE_PHARMACIST']);  

        $pharmacyId = $data['Pharmacy'] ?? null;
        if ($pharmacyId) {
            $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
            if (!$pharmacy) {
                return new JsonResponse(['error' => 'Pharmacy not found'], Response::HTTP_NOT_FOUND);
            }
            $pharmacist->setPharmacy($pharmacy);
        } else {
            return new JsonResponse(['error' => 'Pharmacy ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($pharmacist);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($pharmacist);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacist created successfully'], Response::HTTP_CREATED);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    #[Route('/api/pharmacists/edit/{id}', name: 'api_pharmacist_edit', methods: ['PUT'])]
    public function edit(Request $request, Pharmacist $pharmacist): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        $pharmacist->setFirstName($data['FirstName'] ?? $pharmacist->getFirstName());
        $pharmacist->setLastName($data['LastName'] ?? $pharmacist->getLastName());
        $pharmacist->setEmail($data['Email'] ?? $pharmacist->getEmail());
        $pharmacist->setPassword(password_hash($data['password'] ?? '', PASSWORD_DEFAULT)); 
        $pharmacist->setRoles($data['roles'] ?? ['ROLE_PHARMACIST']);   

        $pharmacyId = $data['Pharmacy'] ?? null;
        if ($pharmacyId) {
            $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
            if (!$pharmacy) {
                return new JsonResponse(['error' => 'Pharmacy not found'], Response::HTTP_NOT_FOUND);
            }
            $pharmacist->setPharmacy($pharmacy);
        }

        $errors = $this->validator->validate($pharmacist);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacist updated successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }



    #[Route('/api/pharmacists/delete/{id}', name: 'api_pharmacist_delete', methods: ['DELETE'])]
    public function delete(Pharmacist $pharmacist): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $this->entityManager->remove($pharmacist);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacist deleted successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    //Utilisée pour un grand nombre d'entités à insérer dans la base de données, au lieu d'insérer chaque entité individuellement
    #[Route('/api/pharmacists/create_bulk', name: 'api_pharmacist_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);
        $pharmacists = [];

        foreach ($data as $pharmacistData) {
            $pharmacist = new Pharmacist();
            $pharmacist->setFirstName($pharmacistData['FirstName'] ?? ''); 
            $pharmacist->setLastName($pharmacistData['LastName'] ?? ''); 
            $pharmacist->setEmail($pharmacistData['Email'] ?? '');
            $pharmacist->setPassword(password_hash($pharmacistData['password'] ?? '', PASSWORD_DEFAULT)); 
            $pharmacist->setRoles($pharmacistData['roles'] ?? ['ROLE_PHARMACIST']); 

            if (isset($pharmacistData['pharmacyId'])) {
                $pharmacyId = $pharmacistData['pharmacyId'];

                $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);

                if (!$pharmacy) {
                    $errors[] = 'Pharmacy not found for pharmacist ' . $pharmacist->getFirstName() . ' ' . $pharmacist->getLastName();
                    continue;
                }

                $pharmacist->setPharmacy($pharmacy);
            }

            $errors = $this->validator->validate($pharmacist);

            if (count($errors) > 0) {
                continue; 
            }

            $pharmacists[] = $pharmacist;
        }

        foreach ($pharmacists as $pharmacist) {
            $this->entityManager->persist($pharmacist);
        }
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacists created successfully'], Response::HTTP_CREATED);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    //Utilisée si vous devez mettre à jour un grand nombre d'entités avec la même modification
    #[Route('/api/pharmacists/edit_bulk', name: 'api_pharmacist_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $pharmacistData) {

            if (!isset($pharmacistData['id'])) {
                continue;
            }

            $pharmacist = $this->pharmacistsRepository->find($pharmacistData['id']);


            if (!$pharmacist) {
                continue;
            }

            $pharmacist->setFirstName($pharmacistData['FirstName'] ?? $pharmacist->getFirstName());
            $pharmacist->setLastName($pharmacistData['LastName'] ?? $pharmacist->getLastName());
            $pharmacist->setEmail($pharmacistData['Email'] ?? $pharmacist->getEmail());
            $pharmacist->setPassword(password_hash($pharmacistData['password'] ?? '', PASSWORD_DEFAULT)); 
            $pharmacist->setRoles($pharmacistData['roles'] ?? ['ROLE_PHARMACIST']); 

            if (isset($pharmacistData['pharmacyId'])) {
                $pharmacyId = $pharmacistData['pharmacyId'];
                $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
                

                if ($pharmacy) {
                    $pharmacist->setPharmacy($pharmacy);
                }
            }

            $errors = $this->validator->validate($pharmacist);

            if (count($errors) > 0) {
                continue;
            }

            $this->entityManager->flush();
        }

        return new JsonResponse(['message' => 'Pharmacists updated successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


    //Utilisée pour supprimer un grand nombre d'entités
    #[Route('/api/pharmacists/delete_bulk', name: 'api_pharmacist_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $pharmacistId) {
            $pharmacist = $this->entityManager->getRepository(Pharmacist::class)->find($pharmacistId);

            if (!$pharmacist) {
                continue;
            }

            $this->entityManager->remove($pharmacist);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacists deleted successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }


}
