<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    /**
     * Endpoint de login — intercepté par le firewall json_login.
     * Cette méthode ne s'exécute jamais vraiment, le firewall la prend en charge.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Cette ligne ne devrait jamais être atteinte,
        // le json_login du firewall gère la requête avant.
        // Si on arrive ici, c'est que l'auth a échoué.
        return $this->json([
            'error' => 'Authentication required'
        ], 401);
    }

    /**
     * Test pour vérifier qu'on est bien authentifié
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'role' => $user->getRole(),
            'photo' => $user->getPhoto(),
            'telephone' => $user->getTelephone(),
            'pays' => $user->getPays(),
            'ville' => $user->getVille(),
            'genre' => $user->getGenre(),
        ]);
    }
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
public function register(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher,
    ValidatorInterface $validator
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    $user = new User();
    $user->setNom($data['nom'] ?? '');
    $user->setPrenom($data['prenom'] ?? '');
    $user->setEmail($data['email'] ?? '');
    $user->setTelephone($data['telephone'] ?? '');
    $user->setPhoto('https://i.pravatar.cc/150?u=' . uniqid());
    $user->setRole('chercheur');
    $user->setStatut('actif');
    $user->setDateCreation(new \DateTime());
    $user->setDateModification(new \DateTime());

    $hashedPassword = $passwordHasher->hashPassword($user, $data['password'] ?? '');
    $user->setPassword($hashedPassword);

    $errors = $validator->validate($user);
    if (count($errors) > 0) {
        return $this->json(['errors' => (string) $errors], 400);
    }

    $em->persist($user);
    $em->flush();

    return $this->json($user, 201, [], ['groups' => 'user:read']);
}
}
