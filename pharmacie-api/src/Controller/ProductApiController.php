<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductApiController extends AbstractController
{
    private $entityManager;
    private $productRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, ProductRepository $productRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('/api/products', name: 'api_products_index', methods: ['GET'])]
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {
        $products = $this->productRepository->findAll();

        // Check if products exist
        if (empty($products)) {
            return new JsonResponse(['message' => 'No product found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($products, 'json');

        return new JsonResponse([
            'message' => 'Products fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/products/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = $this->serializer->serialize($product, 'json');

        return new JsonResponse($data, 200, [], true);
        }else {
            return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/products/create', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to create products'], Response::HTTP_FORBIDDEN);
        }
    
        $data = $request->request->all();
        $product = new Product();
        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? '');
        $product->setRegistrationNumber($data['registrationNumber'] ?? '');
        $product->setPrice(isset($data['price']) ? (float)$data['price'] : null);
    
        $pictureFile = $request->files->get('picture');
        if ($pictureFile) {
            $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
    
            try {
                $pictureFile->move(
                    $this->getParameter('productImagesDirectory'),
                    $newFilename
                );
    
                if (!file_exists($this->getParameter('productImagesDirectory') . '/' . $newFilename)) {
                    return $this->json(['error' => 'Failed to move the uploaded file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
    
                $product->setPicture('/images/product_images/' . $newFilename);
                $product->setImageName($newFilename);
                $product->setImageSize(filesize($this->getParameter('productImagesDirectory') . '/' . $newFilename));
                $product->setUpdatedAt(new \DateTimeImmutable());
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    
        $categoryId = $data['Category'] ?? null;
        if ($categoryId) {
            $category = $entityManager->getRepository(ProductCategory::class)->find($categoryId);
            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($category);
        } else {
            return new JsonResponse(['error' => 'Category ID is required'], Response::HTTP_BAD_REQUEST);
        }
    
        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    
        try {
            $entityManager->persist($product);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create product: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    
        return new JsonResponse(['message' => 'Product created successfully'], Response::HTTP_CREATED);
    }
    


    #[Route('/api/products/edit/{id}', name: 'api_product_edit', methods: ['POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, $id): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to edit products'], Response::HTTP_FORBIDDEN);
        }

        $product = $entityManager->getRepository(Product::class)->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();

        $product->setName($data['name'] ?? '');
        $product->setDescription($data['description'] ?? '');
        $product->setRegistrationNumber($data['registrationNumber'] ?? '');
        $product->setPrice(isset($data['price']) ? (float)$data['price'] : null); // Cast price to float

        $pictureFile = $request->files->get('picture');
        if ($pictureFile) {
            // Handle file upload
            $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
            try {
                $pictureFile->move(
                    $this->getParameter('productImagesDirectory'),
                    $newFilename
                );

                $product->setPicture('/images/product_images/' . $newFilename);
                $product->setImageName($newFilename);
                $product->setImageSize(filesize($this->getParameter('productImagesDirectory') . '/' . $newFilename));
            } catch (FileException $e) {
                return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $categoryId = $data['Category'] ?? null;
        if ($categoryId) {
            $category = $entityManager->getRepository(ProductCategory::class)->find($categoryId);
            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($category);
        }

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Product updated successfully'], Response::HTTP_OK);
    }

    #[Route('/api/products/delete/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(Product $product): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Product deleted successfully'], Response::HTTP_OK);
        }else {
            return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/products/create_bulk', name: 'api_products_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to create products'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->request->all();
        $products = [];
        $categoriesCache = [];

        foreach ($data as $productData) {
            $product = new Product();
            $product->setName($productData['name'] ?? '');
            $product->setDescription($productData['description'] ?? '');
            $product->setRegistrationNumber($productData['registrationNumber'] ?? '');
            $product->setPrice(isset($productData['price']) ? (float)$productData['price'] : null); // Cast price to float
            $pictureFile = $request->files->get('picture');
                if ($pictureFile) {
                    // Handle file upload
                    $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                    try {
                        $pictureFile->move(
                            $this->getParameter('productImagesDirectory'),
                            $newFilename
                        );
    
                        $product->setPicture('/images/product_images/' . $newFilename);
                        $product->setImageName($newFilename);
                        $product->setImageSize(filesize($this->getParameter('productImagesDirectory') . '/' . $newFilename));
                    } catch (FileException $e) {
                        return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }

            $categoryId = $productData['Category'] ?? null;
            if ($categoryId) {
                if (!isset($categoriesCache[$categoryId])) {
                    $category = $entityManager->getRepository(ProductCategory::class)->find($categoryId);
                    if (!$category) {
                        return new JsonResponse(['error' => "Category not found for ID $categoryId"], Response::HTTP_NOT_FOUND);
                    }
                    $categoriesCache[$categoryId] = $category;
                }
                $product->setCategory($categoriesCache[$categoryId]);
            } else {
                return new JsonResponse(['error' => 'Category ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $errors = $validator->validate($product);
            if (count($errors) === 0) {
                $products[] = $product;
            } else {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        foreach ($products as $product) {
            $entityManager->persist($product);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Products created successfully'], Response::HTTP_CREATED);
    }


    #[Route('/api/products/edit_bulk', name: 'api_products_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        if (!$this->isGranted('ROLE_ADMIN_PHARMACY')) {
            return new JsonResponse(['message' => 'This user is not allowed to edit products'], Response::HTTP_FORBIDDEN);
        }

        $data = $request->request->all();
        $categoriesCache = [];

        foreach ($data as $productData) {
            $productId = $productData['id'] ?? null;
            if (!$productId) {
                return new JsonResponse(['error' => 'Product ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $product = $entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return new JsonResponse(['error' => "Product not found for ID $productId"], Response::HTTP_NOT_FOUND);
            }

            $product->setName($productData['name'] ?? $product->getName());
            $product->setDescription($productData['description'] ?? $product->getDescription());
            $product->setRegistrationNumber($productData['registrationNumber'] ?? $product->getRegistrationNumber());
            $product->setPrice(isset($productData['price']) ? (float)$productData['price'] : $product->getPrice()); // Cast price to float

            $pictureFile = $request->files->get('picture');
                    if ($pictureFile) {
                        // Handle file upload
                        $newFilename = uniqid() . '.' . $pictureFile->guessExtension();
                        try {
                            $pictureFile->move(
                                $this->getParameter('productImagesDirectory'),
                                $newFilename
                            );

                            $product->setPicture('/images/product_images/' . $newFilename);
                            $product->setImageName($newFilename);
                            $product->setImageSize(filesize($this->getParameter('productImagesDirectory') . '/' . $newFilename));
                        } catch (FileException $e) {
                            return $this->json(['error' => 'Failed to upload image: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                        }
                    }

            $categoryId = $productData['Category'] ?? null;
            if ($categoryId) {
                if (!isset($categoriesCache[$categoryId])) {
                    $category = $entityManager->getRepository(ProductCategory::class)->find($categoryId);
                    if (!$category) {
                        return new JsonResponse(['error' => "Category not found for ID $categoryId"], Response::HTTP_NOT_FOUND);
                    }
                    $categoriesCache[$categoryId] = $category;
                }
                $product->setCategory($categoriesCache[$categoryId]);
            }

            $errors = $validator->validate($product);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $entityManager->persist($product);
        }

        $entityManager->flush();

        return new JsonResponse(['message' => 'Products updated successfully'], Response::HTTP_OK);
    }


    #[Route('/api/products/delete_bulk', name: 'api_products_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN_PHARMACY')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $productId) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);

            if ($product) {
                $this->entityManager->remove($product);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Products deleted successfully'], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);

    }
}
}
