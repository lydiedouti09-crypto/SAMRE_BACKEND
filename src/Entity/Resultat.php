<?php

namespace App\Entity;

use App\Repository\ResultatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: ResultatRepository::class)]
class Resultat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['resultat:read',])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['resultat:read',])]
    private ?string $reponse = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resultat:read',])]
    private ?string $resultat = null;

    #[ORM\Column(length: 255)]
    #[Groups(['resultat:read',])]
    private ?string $statutValidation = null;

    #[ORM\Column]
    #[Groups(['resultat:read',])]
    private ?\DateTime $dateSoumission = null;

    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[Groups(['resultat:read',])]
    private ?Participation $participation = null;

    #[ORM\ManyToOne(inversedBy: 'resultats')]
    #[Groups(['resultat:read',])]
    private ?Etape $etape = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(string $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(string $resultat): static
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getStatutValidation(): ?string
    {
        return $this->statutValidation;
    }

    public function setStatutValidation(string $statutValidation): static
    {
        $this->statutValidation = $statutValidation;

        return $this;
    }

    public function getDateSoumission(): ?\DateTime
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTime $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

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
