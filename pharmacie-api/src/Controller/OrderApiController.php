<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Delivery;
use App\Entity\Order;
use App\Entity\Pharmacist;
use App\Entity\Prescription;
use App\Entity\Product;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

class OrderApiController extends AbstractController
{
    private $entityManager;
    private $orderRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, OrderRepository $orderRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Récuperation de tous les commandes
     *
     * @return Response
     */
    #[Route('/api/orders', name: 'api_orders_index', methods: ['GET'])]
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {
        $orders = $this->orderRepository->findAll();
        if (empty($orders)) {
            return new JsonResponse(['message' => 'No Orders found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($orders, 'json');

        return new JsonResponse([
            'message' => 'Orders fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK);
    }
    else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);

    }
   
}

    //Récuperation d'une seule commande
    #[Route('/api/order/{id}', name: 'api_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $data = $this->serializer->serialize($order, 'json');

        return new JsonResponse($data, 200, [], true);
    }

    //Ajouter une commande
    #[Route('/api/order/create', name: 'api_order_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);

        $order = new Order();

        $creationDate = new DateTimeImmutable($data['creationDate'] ?? 'now');
        $order->setCreationDate($creationDate);
        $order->setRegistrationNumber($data['registrationNumber'] ?? ''); 
        $order->setPrice($data['price'] ?? null);
        $order->setQuantity($data['quantity'] ?? null);
        $order->setStatue($data['statue'] ?? null);

        $customerId = $data['customerId'] ?? null;
        if ($customerId) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (!$customer) {
                return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setCustomer($customer);
        } else {
            return new JsonResponse(['error' => 'Customer ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $pharmacistId = $data['pharmacistId'] ?? null;
        if ($pharmacistId) {
            $pharmacist = $this->entityManager->getRepository(Pharmacist::class)->find($pharmacistId);
            if (!$pharmacist) {
                return new JsonResponse(['error' => 'Pharmacist not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setPharmacist($pharmacist);
        } else {
            return new JsonResponse(['error' => 'Pharmacist ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $deliveryId = $data['deliveryId'] ?? null;
        if ($deliveryId) {
            $delivery = $this->entityManager->getRepository(Delivery::class)->find($deliveryId);
            if (!$delivery) {
                return new JsonResponse(['error' => 'Delivery not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setDelivery($delivery);
        } else {
            return new JsonResponse(['error' => 'Delivery ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $productId = $data['productId'] ?? null;
        if ($productId) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setProduct($product);
        } else {
            return new JsonResponse(['error' => 'Product ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $prescriptionId = $data['prescriptionId'] ?? null;
        if ($prescriptionId) {
            $prescription = $this->entityManager->getRepository(Prescription::class)->find($prescriptionId);
            if (!$prescription) {
                return new JsonResponse(['error' => 'Prescription not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setPrescription($prescription);
        } else {
            return new JsonResponse(['error' => 'Prescription ID is required'], Response::HTTP_BAD_REQUEST);
        }
        
        $errors = $validator->validate($order);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Order created successfully'], Response::HTTP_CREATED);
    }


    //Modifier une commande
    #[Route('/api/order/edit/{id}', name: 'api_order_edit', methods: ['PUT'])]
    public function edit(Request $request, Order $order): Response
    {
        $data = json_decode($request->getContent(), true);

        $creationDate = new DateTimeImmutable($data['creationDate'] ?? 'now');
        $order->setRegistrationNumber($data['registrationNumber'] ?? ''); 
        $order->setPrice($data['price'] ?? null);
        $order->setQuantity($data['quantity'] ?? null);
        $order->setStatue($data['statue'] ?? null);

        $customerId = $data['customerId'] ?? null;
        if ($customerId) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (!$customer) {
                return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setCustomer($customer);
        } else {
            return new JsonResponse(['error' => 'Customer ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $pharmacistId = $data['pharmacistId'] ?? null;
        if ($pharmacistId) {
            $pharmacist = $this->entityManager->getRepository(Pharmacist::class)->find($pharmacistId);
            if (!$pharmacist) {
                return new JsonResponse(['error' => 'Pharmacist not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setPharmacist($pharmacist);
        } else {
            return new JsonResponse(['error' => 'Pharmacist ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $deliveryId = $data['deliveryId'] ?? null;
        if ($deliveryId) {
            $delivery = $this->entityManager->getRepository(Delivery::class)->find($deliveryId);
            if (!$delivery) {
                return new JsonResponse(['error' => 'Delivery not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setDelivery($delivery);
        } else {
            return new JsonResponse(['error' => 'Delivery ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $productId = $data['productId'] ?? null;
        if ($productId) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setProduct($product);
        } else {
            return new JsonResponse(['error' => 'Product ID is required'], Response::HTTP_BAD_REQUEST);
        }

        $prescriptionId = $data['prescriptionId'] ?? null;
        if ($prescriptionId) {
            $prescription = $this->entityManager->getRepository(Prescription::class)->find($prescriptionId);
            if (!$prescription) {
                return new JsonResponse(['error' => 'Prescription not found'], Response::HTTP_NOT_FOUND);
            }
            $order->setPrescription($prescription);
        } else {
            return new JsonResponse(['error' => 'Prescription ID is required'], Response::HTTP_BAD_REQUEST);
        }
      

        $errors = $this->validator->validate($order);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Order updated successfully'], Response::HTTP_OK);
    }

    //Supprimer une commande
    #[Route('/api/order/delete/{id}', name: 'api_order_delete', methods: ['DELETE'])]
    public function delete(Order $order): Response
    {
        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Order deleted successfully'], Response::HTTP_OK);
    }

    
    #[Route('/api/orders/create_bulk', name: 'api_orders_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request, ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);
        $response = [];

        foreach ($data as $orderData) {
            $order = new Order();
            $order->setCreationDate(new DateTimeImmutable($orderData['creationDate'] ?? 'now'));
            $order->setRegistrationNumber($orderData['registrationNumber'] ?? ''); 
            $order->setPrice($orderData['price'] ?? null);
            $order->setQuantity($orderData['quantity'] ?? null);
            $order->setStatue($orderData['statue'] ?? null);
            
            $customerId = $orderData['customerId'] ?? null;
            $pharmacistId = $orderData['pharmacistId'] ?? null;
            $deliveryId = $orderData['deliveryId'] ?? null;
            $productId = $orderData['productId'] ?? null;
            $prescriptionId = $orderData['prescriptionId'] ?? null;
            
            // Validate relations
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (!$customer) {
                return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
            }
            $pharmacist = $this->entityManager->getRepository(Pharmacist::class)->find($pharmacistId);
            if (!$pharmacist) {
                return new JsonResponse(['error' => 'Pharmacist not found'], Response::HTTP_NOT_FOUND);
            }
            $delivery = $this->entityManager->getRepository(Delivery::class)->find($deliveryId);
            if (!$delivery) {
                return new JsonResponse(['error' => 'Delivery not found'], Response::HTTP_NOT_FOUND);
            }
            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $prescription = $this->entityManager->getRepository(Prescription::class)->find($prescriptionId);
            if (!$prescription) {
                return new JsonResponse(['error' => 'Prescription not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Set relations
            $order->setCustomer($customer);
            $order->setPharmacist($pharmacist);
            $order->setDelivery($delivery);
            $order->setProduct($product);
            $order->setPrescription($prescription);

            // Validate the order entity
            $errors = $validator->validate($order);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->entityManager->persist($order);
            $response[] = $order;
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Orders created successfully', 'orders' => $response], Response::HTTP_CREATED);
    }

    #[Route('/api/orders/edit_bulk', name: 'api_orders_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request, ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);
        $response = [];

        foreach ($data as $orderData) {
            $orderId = $orderData['id'] ?? null;
            if (!$orderId) {
                return new JsonResponse(['error' => 'Order ID is required'], Response::HTTP_BAD_REQUEST);
            }

            $order = $this->entityManager->getRepository(Order::class)->find($orderId);
            if (!$order) {
                return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }

            // Update order data
            $order->setCreationDate(new DateTimeImmutable($orderData['creationDate'] ?? 'now'));
            $order->setRegistrationNumber($orderData['registrationNumber'] ?? ''); 
            $order->setPrice($orderData['price'] ?? null);
            $order->setQuantity($orderData['quantity'] ?? null);
            $order->setStatue($orderData['statue'] ?? null);
            
            $customerId = $orderData['customerId'] ?? null;
            $pharmacistId = $orderData['pharmacistId'] ?? null;
            $deliveryId = $orderData['deliveryId'] ?? null;
            $productId = $orderData['productId'] ?? null;
            $prescriptionId = $orderData['prescriptionId'] ?? null;

            // Validate relations
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (!$customer) {
                return new JsonResponse(['error' => 'Customer not found'], Response::HTTP_NOT_FOUND);
            }
            $pharmacist = $this->entityManager->getRepository(Pharmacist::class)->find($pharmacistId);
            if (!$pharmacist) {
                return new JsonResponse(['error' => 'Pharmacist not found'], Response::HTTP_NOT_FOUND);
            }
            $delivery = $this->entityManager->getRepository(Delivery::class)->find($deliveryId);
            if (!$delivery) {
                return new JsonResponse(['error' => 'Delivery  not found'], Response::HTTP_NOT_FOUND);
            }
            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            if (!$product) {
                return new JsonResponse(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
            }
            $prescription = $this->entityManager->getRepository(Prescription::class)->find($prescriptionId);
            if (!$prescription) {
                return new JsonResponse(['error' => 'Prescription not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Set relations
            $order->setCustomer($customer);
            $order->setPharmacist($pharmacist);
            $order->setDelivery($delivery);
            $order->setProduct($product);
            $order->setPrescription($prescription);


            // Validate the updated order entity
            $errors = $validator->validate($order);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->entityManager->persist($order);
            $response[] = $order;
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Orders updated successfully', 'orders' => $response], Response::HTTP_OK);
    }

    #[Route('/api/orders/delete_bulk', name: 'api_orders_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        foreach ($data as $orderId) {
            $order = $this->entityManager->getRepository(Order::class)->find($orderId);

            if (!$order) {
                return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($order);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Orders deleted successfully'], Response::HTTP_OK);
    }


}
