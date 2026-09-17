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

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

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
        $user->setPhoto(null);
        $user->setPays(!empty($data['pays']) ? trim($data['pays']) : null);
        $user->setVille(!empty($data['ville']) ? trim($data['ville']) : null);
        $user->setGenre(!empty($data['genre']) ? trim($data['genre']) : null);
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

    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Veuillez saisir une adresse email valide.'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'Aucun compte n\'est associé à cette adresse email.'], 404);
        }

        // Générer un mot de passe temporaire
        $suffix = rand(1000, 9999);
        $newPlainPassword = 'Samre#' . $suffix;

        $hashedPassword = $passwordHasher->hashPassword($user, $newPlainPassword);
        $user->setPassword($hashedPassword);
        $user->setDateModification(new \DateTime());
        $em->flush();

        // Envoi par email via Symfony Mailer
        try {
            $emailMessage = (new Email())
                ->from(new Address('no-reply@samre.com', 'SAMRE'))
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe - SAMRE')
                ->html(
                    '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 24px; border: 1px solid #E2E8F0; border-radius: 16px; background-color: #ffffff;">' .
                    '<div style="text-align: center; margin-bottom: 20px;">' .
                    '<h1 style="color: #0F172A; margin: 0; font-size: 24px; font-weight: bold;">SAMRE</h1>' .
                    '<p style="color: #64748B; font-size: 13px; margin: 4px 0 0 0;">Plateforme de test & opportunités</p>' .
                    '</div>' .
                    '<div style="border-top: 1px solid #F1F5F9; padding-top: 20px;">' .
                    '<h2 style="color: #10B981; font-size: 18px; margin: 0 0 12px 0;">Nouveau mot de passe généré</h2>' .
                    '<p style="color: #334155; font-size: 14px; line-height: 1.6;">Bonjour <strong>' . htmlspecialchars($user->getPrenom() ?? 'utilisateur') . '</strong>,</p>' .
                    '<p style="color: #334155; font-size: 14px; line-height: 1.6;">Vous avez demandé la réinitialisation de votre mot de passe. Voici votre nouveau mot de passe temporaire pour vous connecter :</p>' .
                    '<div style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px; text-align: center; margin: 20px 0;">' .
                    '<span style="display: block; font-size: 12px; color: #64748B; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">Mot de passe temporaire</span>' .
                    '<strong style="font-size: 22px; color: #0F172A; letter-spacing: 2px; font-family: monospace;">' . htmlspecialchars($newPlainPassword) . '</strong>' .
                    '</div>' .
                    '<p style="color: #334155; font-size: 14px; line-height: 1.6;">Vous pouvez désormais vous connecter immédiatement avec ce mot de passe, et le changer à tout moment depuis votre profil.</p>' .
                    '<p style="color: #94A3B8; font-size: 12px; margin-top: 30px; border-top: 1px solid #F1F5F9; padding-top: 16px;">Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet email.</p>' .
                    '</div>' .
                    '</div>'
                );

            $mailer->send($emailMessage);
        } catch (\Throwable $e) {
            // Tolérance si mailer local
        }

        return $this->json([
            'message' => 'Un nouveau mot de passe a été envoyé à votre adresse email avec succès.',
        ]);
    }
}
