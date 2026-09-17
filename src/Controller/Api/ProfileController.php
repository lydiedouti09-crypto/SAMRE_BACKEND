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

        if (isset($data['nom'])) $user->setNom(trim($data['nom']));
        if (isset($data['prenom'])) $user->setPrenom(trim($data['prenom']));
        if (isset($data['email'])) $user->setEmail(trim($data['email']));
        if (isset($data['telephone'])) $user->setTelephone(trim($data['telephone']));
        if (isset($data['photo'])) $user->setPhoto(!empty($data['photo']) ? $data['photo'] : null);
        if (isset($data['pays'])) $user->setPays(!empty($data['pays']) ? trim($data['pays']) : null);
        if (isset($data['ville'])) $user->setVille(!empty($data['ville']) ? trim($data['ville']) : null);
        if (isset($data['genre'])) $user->setGenre(!empty($data['genre']) ? trim($data['genre']) : null);
        $user->setDateModification(new \DateTime());

        $this->em->flush();

        return $this->json($user, 200, [], ['groups' => 'user:read']);
    }
}