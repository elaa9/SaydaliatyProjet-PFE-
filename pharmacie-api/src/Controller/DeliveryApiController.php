<?php

namespace App\Controller;

use App\Entity\Delivery;
use App\Repository\DeliveryBoyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DeliveryApiController extends AbstractController
{

    private $entityManager;
    private $deliveryRepository;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, DeliveryBoyRepository $deliveryRepository, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->deliveryRepository = $deliveryRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    } 

    /**
     * Récuperation de tous les livreurs
     *
     * @return Response
     */
    #[Route('/api/deliveries', name: 'app_delivery_api' , methods: ['GET'])]
    public function index(): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $livreurs = $this->deliveryRepository->findAll();

        if (empty($livreurs)) {
            return new JsonResponse(['message' => 'No delivery found'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize($livreurs, 'json');

        return new JsonResponse([
            'message' => 'Deliveries fetched successfully',
            'data' => json_decode($data, true) 
        ], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Récuperation d'un seul livreur
    #[Route('/api/deliveries/{id}', name: 'api_delivery_show', methods: ['GET'])]
    public function show(Delivery $livreur): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = $this->serializer->serialize($livreur, 'json');

        return new JsonResponse($data, 200, [], true);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Ajouter un livreur
    #[Route('/api/deliveries/add', name: 'api_delivery_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        $livreur = new Delivery();
        $livreur->setFirstName($data['FirstName'] ?? '');
        $livreur->setLastName($data['LastName'] ?? '');
        $livreur->setEmail($data['Email'] ?? '');
        $livreur->setPhoneNumber($data['PhoneNumber'] ?? ''); 
        $plainPassword = $data['plainPassword'] ?? '';
            $livreur->setPlainPassword($plainPassword);
            
            $password = $data['password'] ?? '';
            $livreur->setPassword(password_hash($password, PASSWORD_DEFAULT)); 

            // Vérifier que les mots de passe correspondent
            if ($plainPassword !== $password) {
                return new JsonResponse(['error' => 'Passwords do not match'], Response::HTTP_BAD_REQUEST);
            }

        $livreur->setRoles($data['roles'] ?? ['ROLE_DELIVERY']);


        $errors = $this->validator->validate($livreur);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($livreur);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Delivery created successfully'], Response::HTTP_CREATED);
    }
    else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Modifier un livreur
    #[Route('/api/deliveries/edit/{id}', name: 'api_delivery_edit', methods: ['PUT'])]
    public function edit(Request $request, Delivery $livreur): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        $livreur->setFirstName($data['FirstName'] ?? '');
        $livreur->setLastName($data['LastName'] ?? '');
        $livreur->setEmail($data['Email'] ?? '');
        $livreur->setPhoneNumber($data['PhoneNumber'] ?? ''); 
        $livreur->setPassword(password_hash($data['password'] ?? '', PASSWORD_DEFAULT));
        $livreur->setRoles($data['roles'] ?? ['ROLE_DELIVERY']);

        $errors = $this->validator->validate($livreur);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Delivery updated successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Bloquer un livreur
    #[Route('/api/deliveries/block/{id}', name: 'api_delivery_block', methods: ['PUT'])]
    public function block(Delivery $livreur): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $livreur->setBlocked(true);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Delivery blocked successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }


    //Débloquer un livreur
    #[Route('/api/deliveries/unblock/{id}', name: 'api_delivery_unblock', methods: ['PUT'])]
    public function unblock(Delivery $livreur): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $livreur->setBlocked(false);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Delivery unblocked successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }


    //Supprimer un livreur
    #[Route('/api/deliveries/delete/{id}', name: 'api_delivery_delete', methods: ['DELETE'])]
    public function delete(Delivery $livreur): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $this->entityManager->remove($livreur);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Delivery deleted successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Utilisée pour un grand nombre d'entités à insérer dans la base de données, au lieu d'insérer chaque entité individuellement
    #[Route('/api/deliveries/create_bulk', name: 'api_deliveries_create_bulk', methods: ['POST'])]
    public function createBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);
        $livreurs = [];

        foreach ($data as $livreurData) {
            $livreur = new Delivery();
            $livreur->setFirstName($livreurData['FirstName'] ?? ''); 
            $livreur->setLastName($livreurData['LastName'] ?? ''); 
            $livreur->setEmail($livreurData['Email'] ?? ''); 
            $livreur->setPhoneNumber($livreurData['PhoneNumber'] ?? ''); 
            $livreur->setPassword(password_hash($deliveryData['password'] ?? '', PASSWORD_DEFAULT)); 
            $livreur->setRoles($data['roles'] ?? ['ROLE_DELIVERY']);

            $errors = $this->validator->validate($livreur);

            if (count($errors) > 0) {
                continue; 
            }

            $livreurs[] = $livreur;
        }

        foreach ($livreurs as $livreur) {
            $this->entityManager->persist($livreur);
        }
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Deliveries created successfully'], Response::HTTP_CREATED);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Utilisée si vous devez mettre à jour un grand nombre d'entités avec la même modification
    #[Route('/api/deliveries/edit_bulk', name: 'api_deliveries_edit_bulk', methods: ['PUT'])]
    public function editBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $livreurData) {
            // Check if 'id' key exists in the current $livreurData array
            if (!isset($livreurData['id'])) {
                continue;
            }

            $livreur = $this->deliveryRepository->find($livreurData['id']);

            if (!$livreur) {
                continue;
            }

            $livreur->setFirstName($livreurData['FirstName'] ?? '');
            $livreur->setLastName($livreurData['LastName'] ?? '');
            $livreur->setEmail($livreurData['Email'] ?? '');
            $livreur->setPhoneNumber($livreurData['PhoneNumber'] ?? '');
            $livreur->setPassword(password_hash($deliveryData['password'] ?? '', PASSWORD_DEFAULT)); 
            $livreur->setRoles($data['roles'] ?? ['ROLE_DELIVERY']);

            $errors = $this->validator->validate($livreur);

            if (count($errors) > 0) {
                continue; 
            }

            $this->entityManager->persist($livreur);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Deliveries updated successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }


    //Utilisée pour bloquer un grand nombre de livreur
    #[Route('/api/deliveries/block_bulk', name: 'api_deliveries_block_bulk', methods: ['PUT'])]
    public function blockBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $livreurId) {
            $livreur = $this->deliveryRepository->find($livreurId);

            if (!$livreur) {
                continue; 
            }

            $livreur->setBlocked(true);
            $this->entityManager->persist($livreur);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Deliveries blocked successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }

    //Utilisée pour débloquer un grand nombre de livreur
    #[Route('/api/deliveries/unblock_bulk', name: 'api_deliveries_unblock_bulk', methods: ['PUT'])]
    public function unblockBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);

        foreach ($data as $livreurId) {
            $livreur = $this->deliveryRepository->find($livreurId);

            if (!$livreur) {
                continue; 
            }

            $livreur->setBlocked(false);
            $this->entityManager->persist($livreur);
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Deliveries unblocked successfully'], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }


    //Utilisée pour supprimer un grand nombre d'entités
    #[Route('/api/deliveries/delete_bulk', name: 'api_delivery_delete_bulk', methods: ['DELETE'])]
    public function deleteBulk(Request $request): Response
    {
        if($this->isGranted('ROLE_ADMIN')) {

        $data = json_decode($request->getContent(), true);
        $deletedDeliverys = [];

        foreach ($data as $livreurId) {
            $livreur = $this->deliveryRepository->find($livreurId);

            if (!$livreur) {
                continue; 
            }

            $this->entityManager->remove($livreur);
            $deletedDeliverys[] = $livreur;
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Deliveries deleted successfully', 'deleted_deliverys' => $deletedDeliverys], Response::HTTP_OK);
    }else {
        return new JsonResponse(['message' => 'This user not allowed to get Customers'], Response::HTTP_BAD_REQUEST);
    }
   
    }
}
