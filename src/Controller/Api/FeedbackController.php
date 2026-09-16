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
        $data = json_decode($request->getContent(), true);

        $participation = $this->participationRepo->find($data['participationId'] ?? 0);
        if (!$participation || $participation->getUtilisateur() !== $user) {
            return $this->json(['error' => 'Invalid participation'], 400);
        }

        $commentaire = new Commentaire();
        $commentaire->setParticipation($participation);
        $commentaire->setUtilisateur($user);
        $commentaire->setNote($data['note']);
        $commentaire->setFaciliteUtilisation($data['faciliteUtilisation']);
        $commentaire->setPointsPositifs($data['pointsPositifs'] ?? null);
        $commentaire->setProblemes($data['problemes'] ?? null);
        $commentaire->setDifficultes($data['difficultes'] ?? null);
        $commentaire->setAmeliorations($data['ameliorations'] ?? null);
        $commentaire->setCommentaires($data['commentaires'] ?? null);
        $commentaire->setDateCreation(new \DateTime());

        $this->em->persist($commentaire);

        $participation->setStatut('remuneration_en_attente');
        $this->em->flush();

        return $this->json($commentaire, 201, [], ['groups' => 'commentaire:read']);
    }
}