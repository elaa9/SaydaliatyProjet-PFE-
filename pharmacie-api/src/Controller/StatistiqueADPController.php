<?php

namespace App\Controller;

use App\Repository\PharmacistRepository;
use App\Repository\PharmacyRepository;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatistiqueADPController extends AbstractController
{
    #[Route('/api/statistiqueADP', name: 'app_statistique_adp',  methods:['GET'])]
    public function index(
        PharmacyRepository $pharmacyRepository,
        PharmacistRepository $pharmacistsRepository,
        ProductCategoryRepository $productCategoryRepository,
        ProductRepository $productRepository,
    ): JsonResponse {

        $stats = [
            'totalPharmacies' => $pharmacyRepository->count([]),
            'totalPharmaciens' => $pharmacistsRepository->count([]),
            'totalCategories' => $productCategoryRepository->count([]),
            'totalProduits' => $productRepository->count([]),
        ];

        return new JsonResponse($stats);
    }
}
