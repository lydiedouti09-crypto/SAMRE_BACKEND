<?php

namespace App\Entity;

use App\Repository\ReferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: ReferenceRepository::class)]
class Reference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reference:read',])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['reference:read',])]
    private ?string $reference = null;

    #[ORM\Column]
    #[Groups(['reference:read',])]
    private ?\DateTime $dateGeneration = null;

    #[ORM\Column]
    #[Groups(['reference:read',])]
    private ?\DateTime $dateExpiration = null;

    #[ORM\Column(length: 255)]
    #[Groups(['reference:read',])]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['reference:read',])]
    private ?\DateTime $dateValidation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['reference:read',])]
    private ?string $referenceSaisie = null;

    #[ORM\ManyToOne]
    private ?Mission $mission = null;

    #[ORM\ManyToOne]
    private ?Participation $participation = null;

    #[ORM\ManyToOne]
    private ?Etape $etape = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getDateGeneration(): ?\DateTime
    {
        return $this->dateGeneration;
    }

    public function setDateGeneration(\DateTime $dateGeneration): static
    {
        $this->dateGeneration = $dateGeneration;

        return $this;
    }

    public function getDateExpiration(): ?\DateTime
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTime $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateValidation(): ?\DateTime
    {
        return $this->dateValidation;
    }

    public function setDateValidation(\DateTime $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getReferenceSaisie(): ?string
    {
        return $this->referenceSaisie;
    }

    public function setReferenceSaisie(string $referenceSaisie): static
    {
        $this->referenceSaisie = $referenceSaisie;

        return $this;
    }

    public function getMission(): ?Mission
    {
        return $this->mission;
    }

    public function setMission(?Mission $mission): static
    {
        $this->mission = $mission;

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

    public function getEtape(): ?Etape
    {
        return $this->etape;
    }

    public function setEtape(?Etape $etape): static
    {
        $this->etape = $etape;

        return $this;
    }
}
