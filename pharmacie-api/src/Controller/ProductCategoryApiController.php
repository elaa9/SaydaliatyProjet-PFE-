<?php

namespace App\Controller;

use App\Entity\ProductCategory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Repository\ProductCategoryRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;



class ProductCategoryApiController extends AbstractController
{

    private $entityManager;
    private $categoriesRepository;
    private $serializer;
    private $validator;
    // private $uploadsDirectory;
    // private $slugger;


    public function __construct(
        EntityManagerInterface $entityManager, 
        ProductCategoryRepository $categoriesRepository, 
        SerializerInterface $serializer, 
        ValidatorInterface $validator,
        // string $uploadsDirectory,
        // SluggerInterface $slugger,
        )
    {
        $this->entityManager = $entityManager;
        $this->categoriesRepository = $categoriesRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        // $this->uploadsDirectory = $uploadsDirectory;
        // $this->slugger = $slugger;

    } 

    #[Route('/api/categories', name: 'app_product_category_api', methods: ['GET'])]
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $categories = $this->categoriesRepository->findAll();

        // Check if categories exist
        if (empty($categories)) {
            return new JsonResponse(['message' => 'No categorie found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($categories, 'json');

        return new JsonResponse([
            'message' => 'Categories fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK);
    } else {
        return new JsonResponse(['message' => 'This user not allowed to get categories'], Response::HTTP_BAD_REQUEST);

    }
   
    }

    #[Route('/api/categories/{id}', name: 'api_category_show', methods: ['GET'])]
    public function show(ProductCategory $categorie): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = $this->serializer->serialize($categorie, 'json');

        return new JsonResponse($data, 200, [], true);
        }
    else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/categories/create', name: 'api_category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to create categories'], Response::HTTP_FORBIDDEN);
        }
    
        $data = $request->request->all();
        $category = new ProductCategory();
        $category->setName($data['Name'] ?? '');
    
        $picture = $request->files->get('picture');
        if ($picture) {
            $newFilename = uniqid() . '.' . $picture->guessExtension();
    
            try {
                $picture->move(
                    $this->getParameter('uploadsDirectory'),
                    $newFilename
                );
    
                if (!file_exists($this->getParameter('uploadsDirectory') . '/' . $newFilename)) {
                    return $this->json(['error' => 'Failed to move the uploaded file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
    
                $category->setPicture('/images/category_images/' . $newFilename);
                $category->setImageName($newFilename);
                $category->setImageSize(filesize($this->getParameter('uploadsDirectory') . '/' . $newFilename));
                $category->setUpdatedAt(new \DateTimeImmutable());
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    
        try {
            $entityManager->persist($category);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create category: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        return new JsonResponse(['message' => 'Category created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/api/categories/edit/{id}', name: 'api_category_edit', methods: ['POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        if ($this->isGranted('ROLE_ADMIN_PHARMACY')) {
            $productCategory = $entityManager->getRepository(ProductCategory::class)->find($id);

            if (!$productCategory) {
                return $this->json(['error' => 'Category not found.'], Response::HTTP_NOT_FOUND);
            }

            $name = $request->request->get('Name');
            $pictureFile = $request->files->get('picture');

            if ($name) {
                $productCategory->setName($name);
            }

            if ($pictureFile) {
                // Handle file upload for picture
                $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                try {
                    $pictureFile->move(
                        $this->getParameter('uploadsDirectory'),
                        $newFilename
                    );

                    $productCategory->setPicture('/images/category_images/' . $newFilename);
                    $productCategory->setImageName($newFilename);
                    $productCategory->setImageSize(filesize($this->getParameter('uploadsDirectory') . '/' . $newFilename));
                } catch (FileException $e) {
                    return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            $productCategory->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($productCategory);
            $entityManager->flush();

            return $this->json(['success' => 'Product category updated successfully.'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to edit categories'], Response::HTTP_FORBIDDEN);
        }
    }

    #[Route('/api/categories/delete/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function delete(ProductCategory $categorie): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $this->entityManager->remove($categorie);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Category deleted successfully'], Response::HTTP_OK);
    }  else {
        return new JsonResponse(['message' => 'This user not allowed to delete categories'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/categories/create_bulk', name: 'api_category_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN_PHARMACY')) {
            $data = json_decode($request->getContent(), true);
            $categories = [];
    
            foreach ($data as $categoryData) {
                $category = new ProductCategory();
                $category->setName($categoryData['Name'] ?? '');
    
                $pictureFile = $request->files->get('picture');
                if ($pictureFile) {
                    // Handle file upload
                    $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                    try {
                        $pictureFile->move(
                            $this->getParameter('uploadsDirectory'),
                            $newFilename
                        );
    
                        $category->setPicture('/images/category_images/' . $newFilename);
                        $category->setImageName($newFilename);
                        $category->setImageSize(filesize($this->getParameter('uploadsDirectory') . '/' . $newFilename));
                    } catch (FileException $e) {
                        return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
    
                $errors = $this->validator->validate($category);
                if (count($errors) === 0) {
                    $this->entityManager->persist($category);
                    $categories[] = $category;
                }
            }
    
            $this->entityManager->flush();
    
            return new JsonResponse(['message' => 'Categories created successfully'], Response::HTTP_CREATED);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to add categories'], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/api/categories/edit_bulk', name: 'api_category_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN_PHARMACY')) {
            $data = json_decode($request->getContent(), true);

            foreach ($data as $categoryData) {
                $categoryId = $categoryData['id'] ?? null;
                $category = $this->entityManager->getRepository(ProductCategory::class)->find($categoryId);

                if ($category) {
                    $category->setName($categoryData['Name'] ?? $category->getName());

                    $pictureFile = $request->files->get('picture');
                    if ($pictureFile) {
                        // Handle file upload
                        $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                        try {
                            $pictureFile->move(
                                $this->getParameter('uploadsDirectory'),
                                $newFilename
                            );

                            $category->setPicture('/images/category_images/' . $newFilename);
                            $category->setImageName($newFilename);
                            $category->setImageSize(filesize($this->getParameter('uploadsDirectory') . '/' . $newFilename));
                        } catch (FileException $e) {
                            return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }

                    $errors = $this->validator->validate($category);
                    if (count($errors) === 0) {
                        $this->entityManager->persist($category);
                    }
                }
            }

            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Categories updated successfully'], Response::HTTP_OK);
        } else {
            return new JsonResponse(['message' => 'This user is not allowed to edit categories'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/categories/delete_bulk', name: 'api_category_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $categoryId) {
            $category = $this->entityManager->getRepository(ProductCategory::class)->find($categoryId);

            if ($category) {
                $this->entityManager->remove($category);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Categories deleted successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);

    }
   
}



}