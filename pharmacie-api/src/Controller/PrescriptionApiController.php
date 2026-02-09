<?php

namespace App\Controller;

use App\Entity\Prescription;
use App\Repository\PrescriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PrescriptionApiController extends AbstractController
{
    private $entityManager;
    private $prescriptionRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, PrescriptionRepository $prescriptionRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->prescriptionRepository = $prescriptionRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/api/prescriptions', name: 'app_prescription_api', methods: ['GET'])]
    public function index(): Response
    {
        $prescriptions = $this->prescriptionRepository->findAll();
        $data = $this->serializer->serialize($prescriptions, 'json');

        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/api/prescription/{id}', name: 'api_prescription_show', methods: ['GET'])]
    public function show(Prescription $prescription): Response
    {
        $data = $this->serializer->serialize($prescription, 'json');

        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/api/prescriptions/create', name: 'api_prescription_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $prescription = new Prescription();
        $prescription->setName($request->request->get('Name'));
        $prescription->setDateIssue(new \DateTimeImmutable($request->request->get('DateIssue')));

        $pictureFile = $request->files->get('picture');
        if ($pictureFile) {
            $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
            try {
                $pictureFile->move(
                    $this->getParameter('prescriptionImagesDirectory'),
                    $newFilename
                );

                $imagePath = $this->getParameter('prescriptionImagesDirectory') . '/' . $newFilename;
                if (file_exists($imagePath)) {
                    $prescription->setPicture('/images/prescription_images/' . $newFilename);
                    $prescription->setImageName($newFilename);
                    $prescription->setImageSize(filesize($imagePath));
                } else {
                    $prescription->setImageSize(0); // Set a default value if the file doesn't exist
                }
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $entityManager->persist($prescription);
        $entityManager->flush();

        return $this->json(['success' => 'Prescription created successfully.'], Response::HTTP_CREATED);
    }
     else {
        return new JsonResponse(['message' => 'This user is not allowed to edit categories'], Response::HTTP_FORBIDDEN);
        }
    }

    // Update an existing prescription
    #[Route('/api/prescriptions/edit/{id}', name: 'api_prescription_update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $prescription = $entityManager->getRepository(Prescription::class)->find($id);

        if (!$prescription) {
            return $this->json(['error' => 'Prescription not found.'], Response::HTTP_NOT_FOUND);
        }

        $name = $request->request->get('Name');
            $pictureFile = $request->files->get('picture');

            if ($name) {
                $prescription->setName($name);
            }

            if ($pictureFile) {
                // Handle file upload
                $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                try {
                    $pictureFile->move(
                        $this->getParameter('prescriptionImagesDirectory'),
                        $newFilename
                    );

                    $prescription->setPicture('/images/prescription_images/' . $newFilename);
                    $prescription->setImageName($newFilename);
                    $prescription->setImageSize(filesize($this->getParameter('prescriptionImagesDirectory') . '/' . $newFilename));
                } catch (FileException $e) {
                    return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $prescription->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($prescription);
            $entityManager->flush();

        return $this->json(['success' => 'Prescription updated successfully.'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to edit categories'], Response::HTTP_FORBIDDEN);
        }
    }

    // Delete a prescription
    #[Route('/api/prescriptions/delete/{id}', name: 'api_prescription_delete', methods: ['DELETE'])]
    public function delete(EntityManagerInterface $entityManager, $id): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $prescription = $entityManager->getRepository(Prescription::class)->find($id);

        if (!$prescription) {
            return $this->json(['error' => 'Prescription not found.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($prescription);
        $entityManager->flush();

        return $this->json(['success' => 'Prescription deleted successfully.'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to edit categories'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/prescriptions/create_bulk', name: 'api_prescription_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
        $data = json_decode($request->getContent(), true);
        $prescriptions = [];

        foreach ($data as $prescriptionData) {
            $prescription = new Prescription();
            $prescription->setName($prescriptionData['Name'] ?? '');
            $prescription->setDateIssue(new \DateTimeImmutable($prescriptionData['DateIssue'] ?? ''));

            $pictureFile = isset($prescriptionData['picture']) ? $prescriptionData['picture'] : null;
            if ($pictureFile) {
                $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                try {
                    $pictureFile->move(
                        $this->getParameter('prescriptionImagesDirectory'),
                        $newFilename
                    );

                    $imagePath = $this->getParameter('prescriptionImagesDirectory') . '/' . $newFilename;
                    if (file_exists($imagePath)) {
                        $prescription->setPicture('/images/prescription_images/' . $newFilename);
                        $prescription->setImageName($newFilename);
                        $prescription->setImageSize(filesize($imagePath));
                    } else {
                        $prescription->setImageSize(0); // Set a default value if the file doesn't exist
                    }
                } catch (FileException $e) {
                    return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $errors = $this->validator->validate($prescription);

            if (count($errors) === 0) {
                $prescriptions[] = $prescription;
            }
        }

        foreach ($prescriptions as $prescription) {
            $entityManager->persist($prescription);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Prescriptions created successfully'], Response::HTTP_CREATED);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to create categories'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/prescriptions/edit_bulk', name: 'api_prescription_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        $updatedPrescriptions = [];

        foreach ($data as $prescriptionData) {
            $prescriptionId = $prescriptionData['id'] ?? null;

            // Find the prescription entity by ID
            $prescription = $entityManager->getRepository(Prescription::class)->find($prescriptionId);

            if (!$prescription) {
                continue; // Skip if prescription not found
            }

            // Update the prescription entity with the new data
            $prescription->setName($prescriptionData['Name'] ?? $prescription->getName());
            $prescription->setDateIssue(new \DateTimeImmutable($prescriptionData['DateIssue'] ?? $prescription->getDateIssue()->format('Y-m-d')));

            $pictureFile = isset($prescriptionData['picture']) ? $prescriptionData['picture'] : null;
            if ($pictureFile) {
                // Handle file upload
                $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                try {
                    $pictureFile->move(
                        $this->getParameter('prescriptionImagesDirectory'),
                        $newFilename
                    );

                    $prescription->setPicture('/images/prescription_images/' . $newFilename);
                    $prescription->setImageName($newFilename);
                    $prescription->setImageSize(filesize($this->getParameter('prescriptionImagesDirectory') . '/' . $newFilename));
                } catch (FileException $e) {
                    return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $prescription->setUpdatedAt(new \DateTimeImmutable());

            $errors = $this->validator->validate($prescription);

            if (count($errors) === 0) {
                $updatedPrescriptions[] = $prescription;
            }
        }

        // Persist the updated prescription entities
        foreach ($updatedPrescriptions as $prescription) {
            $entityManager->persist($prescription);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Prescriptions updated successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user is not allowed to create categories'], Response::HTTP_FORBIDDEN);
        }
    }    


    #[Route('/api/prescriptions/delete_bulk', name: 'api_prescription_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $prescriptionId) {
            $prescription = $this->entityManager->getRepository(Prescription::class)->find($prescriptionId);

            if ($prescription) {
                $this->entityManager->remove($prescription);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Prescriptions deleted successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user is not allowed to edit categories'], Response::HTTP_FORBIDDEN);
        }
    }
}
