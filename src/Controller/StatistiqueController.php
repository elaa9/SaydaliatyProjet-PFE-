<?php

namespace App\Controller;

use App\Repository\AdminPharmacyRepository;
use App\Repository\CustomerRepository;
use App\Repository\DeliveryBoyRepository;
use App\Repository\OrderRepository;
use App\Repository\PharmacyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatistiqueController extends AbstractController
{
    #[Route('/api/statistique', name: 'app_statistique',  methods:['GET'])]
    public function index(
        CustomerRepository $customerRepository,
        DeliveryBoyRepository $livreurRepository,
        PharmacyRepository $pharmacyRepository,
        AdminPharmacyRepository $adminPharmacyRepository,
        OrderRepository $orderRepository
    ): JsonResponse {
        
        $stats = [
            'totalClients' => $customerRepository->count([]),
            'totalLivreurs' => $livreurRepository->count([]),
            'totalPharmacies' => $pharmacyRepository->count([]),
            'totalAdminPharmacie' => $adminPharmacyRepository->count([]),
            'totalOrders' => $orderRepository->count([])
        ];

        return new JsonResponse($stats);
    }
}
