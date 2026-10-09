<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/profile', name: 'api_profile_')]
class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
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
            'statut' => $user->getStatut(),
        ], Response::HTTP_OK);
    }

    #[Route('', name: 'update', methods: ['PUT', 'POST'])]
    public function update(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $contentType = $request->headers->get('Content-Type', '');
        $data = [];

        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true) ?: [];
        } else {
            $data = $request->request->all();
        }

        // Sécurité : L'adresse email ne peut pas être modifiée via ce endpoint simple
        if (isset($data['nom'])) {
            $user->setNom(trim((string) $data['nom']));
        }
        if (isset($data['prenom'])) {
            $user->setPrenom(trim((string) $data['prenom']));
        }
        if (isset($data['telephone'])) {
            $user->setTelephone(trim((string) $data['telephone']));
        }
        if (isset($data['pays'])) {
            $user->setPays(!empty($data['pays']) ? trim((string) $data['pays']) : null);
        }
        if (isset($data['ville'])) {
            $user->setVille(!empty($data['ville']) ? trim((string) $data['ville']) : null);
        }
        if (isset($data['genre'])) {
            $user->setGenre(!empty($data['genre']) ? trim((string) $data['genre']) : null);
        }

        // Traitement sécurisé du téléversement de la photo
        $projectDir = $this->getParameter('kernel.project_dir');

        // 1. Photo envoyée sous forme de fichier multipart
        $uploadedFile = $request->files->get('photo');
        if ($uploadedFile instanceof UploadedFile) {
            try {
                $savedPath = $this->saveUploadedPhotoFile($uploadedFile, $user, $projectDir);
                $user->setPhoto($savedPath);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        } 
        // 2. Photo envoyée sous forme de chaîne Data URI Base64
        elseif (array_key_exists('photo', $data)) {
            $photoPayload = $data['photo'];
            try {
                $savedPath = $this->processBase64Photo($photoPayload, $user, $projectDir);
                $user->setPhoto($savedPath);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        $user->setDateModification(new \DateTime());

        // Validation des données de l'entité User avant persistence
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'error' => implode(' ', $errorMessages),
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            'statut' => $user->getStatut(),
        ], Response::HTTP_OK);
    }

    /**
     * Traite et enregistre une image Base64 sur le disque de façon sécurisée.
     */
    private function processBase64Photo(?string $photoData, User $user, string $projectDir): ?string
    {
        if (empty($photoData) || trim($photoData) === '') {
            $this->deleteOldAvatar($user, $projectDir);
            return null;
        }

        // Si c'est déjà un chemin relatif local existant, on le conserve
        if (str_starts_with($photoData, 'uploads/avatars/')) {
            return $photoData;
        }

        // Extraction du format Data URI Base64
        if (!preg_match('/^data:(image\/(jpeg|png|webp|gif));base64,(.+)$/is', $photoData, $matches)) {
            throw new \InvalidArgumentException('Format de photo Base64 invalide.');
        }

        $rawBase64 = $matches[3];
        $decoded = base64_decode($rawBase64, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Données de photo corrompues ou invalides.');
        }

        // Validation de la taille maximale (2 Mo maximum)
        $maxSizeBytes = 2 * 1024 * 1024;
        if (strlen($decoded) > $maxSizeBytes) {
            throw new \InvalidArgumentException('La taille de la photo ne doit pas dépasser 2 Mo.');
        }

        // Validation stricte du type MIME réel via finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->buffer($decoded);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$realMime])) {
            throw new \InvalidArgumentException('Format d\'image non autorisé. Formats acceptés : JPEG, PNG, WebP.');
        }

        $extension = $allowedMimes[$realMime];
        $fileName = 'avatar_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadDir = $projectDir . '/public/uploads/avatars';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $fileName;
        file_put_contents($targetPath, $decoded);

        // Supprimer l'ancienne photo pour éviter d'encombrer le disque
        $this->deleteOldAvatar($user, $projectDir);

        return 'uploads/avatars/' . $fileName;
    }

    /**
     * Traite et enregistre un fichier téléversé via UploadedFile (multipart/form-data).
     */
    private function saveUploadedPhotoFile(UploadedFile $file, User $user, string $projectDir): string
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Le fichier envoyé est invalide.');
        }

        // Validation taille (2 Mo)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException('La photo ne doit pas dépasser 2 Mo.');
        }

        // Validation MIME réel
        $realMime = $file->getMimeType();
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$realMime])) {
            throw new \InvalidArgumentException('Format de fichier non autorisé. Formats acceptés : JPEG, PNG, WebP.');
        }

        $extension = $allowedMimes[$realMime];
        $fileName = 'avatar_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadDir = $projectDir . '/public/uploads/avatars';

        $file->move($uploadDir, $fileName);

        // Supprimer l'ancienne photo
        $this->deleteOldAvatar($user, $projectDir);

        return 'uploads/avatars/' . $fileName;
    }

    private function deleteOldAvatar(User $user, string $projectDir): void
    {
        $oldPhoto = $user->getPhoto();
        if ($oldPhoto && str_starts_with($oldPhoto, 'uploads/avatars/')) {
            $oldPath = $projectDir . '/public/' . $oldPhoto;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
    }
}