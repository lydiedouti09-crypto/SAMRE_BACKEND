<?php

namespace App\Entity;

use App\Repository\CommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?int $note = null;

    #[ORM\Column]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?int $faciliteUtilisation = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?string $pointsPositifs = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?string $problemes = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?string $difficultes = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?string $ameliorations = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['commentaire:read','commentaire:write',])]
    private ?string $commentaires = null;

    #[ORM\Column]
    #[Groups(['commentaire:read',])]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[Groups(['commentaire:read',])]
    private ?Participation $participation = null;

    #[ORM\ManyToOne(inversedBy: 'commentaires')]
    #[Groups(['commentaire:read',])]
    private ?User $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getFaciliteUtilisation(): ?int
    {
        return $this->faciliteUtilisation;
    }

    public function setFaciliteUtilisation(int $faciliteUtilisation): static
    {
        $this->faciliteUtilisation = $faciliteUtilisation;

        return $this;
    }

    public function getPointsPositifs(): ?string
    {
        return $this->pointsPositifs;
    }

    public function setPointsPositifs(string $pointsPositifs): static
    {
        $this->pointsPositifs = $pointsPositifs;

        return $this;
    }

    public function getProblemes(): ?string
    {
        return $this->problemes;
    }

    public function setProblemes(string $problemes): static
    {
        $this->problemes = $problemes;

        return $this;
    }

    public function getDifficultes(): ?string
    {
        return $this->difficultes;
    }

    public function setDifficultes(string $difficultes): static
    {
        $this->difficultes = $difficultes;

        return $this;
    }

    public function getAmeliorations(): ?string
    {
        return $this->ameliorations;
    }

    public function setAmeliorations(string $ameliorations): static
    {
        $this->ameliorations = $ameliorations;

        return $this;
    }

    public function getCommentaires(): ?string
    {
        return $this->commentaires;
    }

    public function setCommentaires(string $commentaires): static
    {
        $this->commentaires = $commentaires;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getParticipation(): ?Participation
    {
        return $this->participation;
    }

    public function setParticipation(?Participation $participation): static
    {
        $this->participation = $participation;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
