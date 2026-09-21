<?php

namespace App\Controller\Api;

use App\Entity\Commentaire;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/feedback', name: 'api_feedback_')]
class FeedbackController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ParticipationRepository $participationRepo
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $participation = $this->participationRepo->find($data['participationId'] ?? 0);
        if (!$participation || $participation->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Participation invalide'], 400);
        }

        $commentaire = new Commentaire();
        $commentaire->setParticipation($participation);
        $commentaire->setUtilisateur($user);

        $note = isset($data['note']) ? (int)$data['note'] : 5;
        if ($note < 1) $note = 1;
        if ($note > 5) $note = 5;
        $commentaire->setNote($note);

        $facilite = 4;
        if (isset($data['faciliteUtilisation'])) {
            $fVal = $data['faciliteUtilisation'];
            if (is_numeric($fVal)) {
                $facilite = (int)$fVal;
            } else {
                $map = ['Très facile' => 5, 'Facile' => 4, 'Moyenne' => 3, 'Difficile' => 2];
                $facilite = $map[$fVal] ?? 4;
            }
        }
        $commentaire->setFaciliteUtilisation($facilite);

        // Si c'est un commentaire du jour, on le préfixe proprement avec le numéro du jour
        $jour = !empty($data['jour']) ? (int)$data['jour'] : null;
        $commText = trim($data['commentaires'] ?? '');
        if ($jour && !empty($commText) && !str_starts_with($commText, "[Jour $jour]")) {
            $commText = "[Jour $jour] " . $commText;
        }

        $commentaire->setPointsPositifs(!empty($data['pointsPositifs']) ? trim($data['pointsPositifs']) : null);
        $commentaire->setProblemes(!empty($data['problemes']) ? trim($data['problemes']) : null);
        $commentaire->setDifficultes(!empty($data['difficultes']) ? trim($data['difficultes']) : null);
        $commentaire->setAmeliorations(!empty($data['ameliorations']) ? trim($data['ameliorations']) : null);
        $commentaire->setCommentaires(!empty($commText) ? $commText : null);
        $commentaire->setDateCreation(new \DateTime());

        $this->em->persist($commentaire);

        // N'activer 'remuneration_en_attente' que pour la clôture finale de la mission
        if (!empty($data['isFinal']) || $participation->getProgression() >= 100) {
            $participation->setStatut('remuneration_en_attente');
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Feedback enregistré avec succès',
            'id' => $commentaire->getId(),
        ], 201);
    }
}