<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profile', name: 'api_profile_')]
class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return $this->json($this->getUser(), 200, [], ['groups' => 'user:read']);
    }

    #[Route('', name: 'update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) $user->setNom($data['nom']);
        if (isset($data['prenom'])) $user->setPrenom($data['prenom']);
        if (isset($data['email'])) $user->setEmail($data['email']);
        if (isset($data['telephone'])) $user->setTelephone($data['telephone']);
        if (isset($data['photo'])) $user->setPhoto($data['photo']);
        if (isset($data['pays'])) $user->setPays($data['pays']);
        if (isset($data['ville'])) $user->setVille($data['ville']);
        if (isset($data['genre'])) $user->setGenre($data['genre']);
        $user->setDateModification(new \DateTime());

        $this->em->flush();

        return $this->json($user, 200, [], ['groups' => 'user:read']);
    }
}