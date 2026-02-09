<?php

namespace App\Controller;

use App\Entity\Pharmacy;
use App\Repository\PharmacyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PharmacyApiController extends AbstractController
{

    private $entityManager;
    private $pharmacyRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, PharmacyRepository $pharmacyRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->pharmacyRepository = $pharmacyRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    } 

    #[Route('/api/pharmacies', name: 'app_pharmacy_api' , methods: ['GET'])]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_PHARMACY')) {
        $pharmacies = $this->pharmacyRepository->findAll();
        if (empty($pharmacies)) {
            return new JsonResponse(['message' => 'No pharmacy found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($pharmacies, 'json');

        return new JsonResponse([
            'message' => 'Pharmacies fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/pharmacies/{id}', name: 'api_pharmacy_show', methods: ['GET'])]
    public function show(Pharmacy $pharmacie): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_PHARMACY')) {
        $data = $this->serializer->serialize($pharmacie, 'json');

        return new JsonResponse($data, 200, [], true);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/pharmacies/add', name: 'api_pharmacy_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to create pharmacies'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->request->all();

        $pharmacy = new Pharmacy();
        $pharmacy->setName($data['Name'] ?? '');
        $pharmacy->setAddress($data['Address'] ?? '');
        $pharmacy->setCity($data['City'] ?? '');

        $pictureFile = $request->files->get('picture');
        if ($pictureFile) {
            $newFilename = uniqid() . '.' . $pictureFile->guessExtension();

            try {
                $pictureFile->move(
                    $this->getParameter('pharmacyImagesDirectory'),
                    $newFilename
                );

                if (!file_exists($this->getParameter('pharmacyImagesDirectory') . '/' . $newFilename)) {
                    return $this->json(['error' => 'Failed to move the uploaded file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $pharmacy->setPicture('/images/pharmacy_images/' . $newFilename);
                $pharmacy->setImageName($newFilename);
                $pharmacy->setImageSize(filesize($this->getParameter('pharmacyImagesDirectory') . '/' . $newFilename));
                $pharmacy->setUpdatedAt(new \DateTimeImmutable());
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $errors = $validator->validate($pharmacy);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($pharmacy);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacy created successfully'], Response::HTTP_CREATED);
    }


    #[Route('/api/pharmacies/edit/{id}', name: 'api_pharmacy_edit', methods: ['POST'])]
    public function edit(Request $request, Pharmacy $pharmacie,$id,EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to edit pharmacies'], Response::HTTP_FORBIDDEN);
        }

        $pharmacie = $entityManager->getRepository(Pharmacy::class)->find($id);
        if (!$pharmacie) {
            return $this->json(['error' => 'Pharmacy not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();

        $pharmacie->setName($data['Name'] ?? ''); 
        $pharmacie->setAddress($data['Address'] ?? ''); 
        $pharmacie->setCity($data['City'] ?? '');
        $pictureFile = $request->files->get('picture');
        if ($pictureFile) {
            // Handle file upload
            $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
            try {
                $pictureFile->move(
                    $this->getParameter('pharmacyImagesDirectory'),
                    $newFilename
                );

                $pharmacie->setPicture('/images/pharmacy_images/' . $newFilename);
                $pharmacie->setImageName($newFilename);
                $pharmacie->setImageSize(filesize($this->getParameter('pharmacyImagesDirectory') . '/' . $newFilename));
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        
        $errors = $this->validator->validate($pharmacie);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacy updated successfully'], Response::HTTP_OK);
    }

    #[Route('/api/pharmacies/delete/{id}', name: 'api_pharmacy_delete', methods: ['DELETE'])]
    public function delete(Pharmacy $pharmacie): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_PHARMACY')) {
            $this->entityManager->remove($pharmacie);
            $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacy deleted successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/pharmacies/create_bulk', name: 'api_pharmacy_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $createdPharmacies = [];

        foreach ($data as $pharmacyData) {
            $pharmacy = new Pharmacy();
            $pharmacy->setName($pharmacyData['Name'] ?? '');
            $pharmacy->setAddress($pharmacyData['Address'] ?? '');
            $pharmacy->setCity($pharmacyData['City'] ?? '');

            $pictureFile = $pharmacyData['picture'] ?? null;
            if ($pictureFile) {
                $newFilename = uniqid() . '.' . pathinfo($pictureFile['name'], PATHINFO_EXTENSION);

                try {
                    $pictureFile['tmp_name']->move(
                        $this->getParameter('pharmacyImagesDirectory'),
                        $newFilename
                    );

                    if (!file_exists($this->getParameter('pharmacyImagesDirectory') . '/' . $newFilename)) {
                        return new JsonResponse(['error' => 'Failed to move the uploaded file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    $pharmacy->setPicture('/images/pharmacy_images/' . $newFilename);
                    $pharmacy->setImageName($newFilename);
                    $pharmacy->setImageSize(filesize($this->getParameter('pharmacyImagesDirectory') . '/' . $newFilename));
                    $pharmacy->setUpdatedAt(new \DateTimeImmutable());
                } catch (FileException $e) {
                    return new JsonResponse(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $errors = $validator->validate($pharmacy);

            if (count($errors) === 0) {
                $createdPharmacies[] = $pharmacy;
            }
        }

        foreach ($createdPharmacies as $pharmacy) {
            $entityManager->persist($pharmacy);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacies created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/api/pharmacies/edit_bulk', name: 'api_pharmacy_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        foreach ($data as $pharmacyData) {
            $pharmacyId = $pharmacyData['id'] ?? null;
            if (!$pharmacyId) {
                continue;
            }

            $pharmacy = $entityManager->getRepository(Pharmacy::class)->find($pharmacyId);
            if (!$pharmacy) {
                continue;
            }

            $pharmacy->setName($pharmacyData['Name'] ?? $pharmacy->getName());
            $pharmacy->setAddress($pharmacyData['Address'] ?? $pharmacy->getAddress());
            $pharmacy->setCity($pharmacyData['City'] ?? $pharmacy->getCity());

            $pictureFile = $pharmacyData['picture'] ?? null;
            if ($pictureFile) {
                $newFilename = uniqid() . '.' . pathinfo($pictureFile['name'], PATHINFO_EXTENSION);

                try {
                    $pictureFile['tmp_name']->move(
                        $this->getParameter('pharmacyImagesDirectory'),
                        $newFilename
                    );

                    $pharmacy->setPicture('/images/pharmacy_images/' . $newFilename);
                    $pharmacy->setImageName($newFilename);
                    $pharmacy->setImageSize(filesize($this->getParameter('pharmacyImagesDirectory') . '/' . $newFilename));
                } catch (FileException $e) {
                    return new JsonResponse(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $errors = $validator->validate($pharmacy);
            if (count($errors) > 0) {
                continue;
            }

            $entityManager->persist($pharmacy);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacies updated successfully'], Response::HTTP_OK);
    }

    #[Route('/api/pharmacies/delete_bulk', name: 'api_pharmacy_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $pharmacyId) {
            $pharmacy = $this->entityManager->getRepository(Pharmacy::class)->find($pharmacyId);

            if ($pharmacy) {
                $this->entityManager->remove($pharmacy);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Pharmacies deleted successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to access this resource'], Response::HTTP_FORBIDDEN);
        }
    }

}
