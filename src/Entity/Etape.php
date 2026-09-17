<?php

namespace App\Entity;

use App\Repository\EtapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: EtapeRepository::class)]
class Etape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['etape:read',])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etape:read',])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['etape:read',])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['etape:read',])]
    private ?string $instruction = null;

    #[ORM\Column]
    #[Groups(['etape:read',])]
    private ?int $ordre = null;

    #[ORM\Column]
    #[Groups(['etape:read',])]
    private ?int $jour = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etape:read',])]
    private ?string $resultatAttendu = null;

    #[ORM\Column]
    #[Groups(['etape:read',])]
    private ?bool $besoinReference = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etape:read',])]
    private ?string $dureeEstimee = null;

    #[ORM\Column(length: 255)]
    #[Groups(['etape:read',])]
    private ?string $statut = null;

    #[ORM\Column]
    #[Groups(['etape:read',])]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'etapes')]
    #[Groups(['etape:read',])]
    private ?Mission $mission = null;

    /**
     * @var Collection<int, Resultat>
     */
    #[ORM\OneToMany(targetEntity: Resultat::class, mappedBy: 'etape')]
    #[Groups(['etape:read',])]
    private Collection $resultats;

    /**
     * @var Collection<int, Reference>
     */
    #[ORM\OneToMany(targetEntity: Reference::class, mappedBy: 'etape')]
    #[Groups(['etape:read',])]
    private Collection $participation;

    public function __construct()
    {
        $this->resultats = new ArrayCollection();
        $this->participation = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getInstruction(): ?string
    {
        return $this->instruction;
    }

    public function setInstruction(string $instruction): static
    {
        $this->instruction = $instruction;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instruction;
    }

    public function setInstructions(string $instructions): static
    {
        $this->instruction = $instructions;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getJour(): ?int
    {
        return $this->jour;
    }

    public function setJour(int $jour): static
    {
        $this->jour = $jour;

        return $this;
    }

    public function getResultatAttendu(): ?string
    {
        return $this->resultatAttendu;
    }

    public function setResultatAttendu(string $resultatAttendu): static
    {
        $this->resultatAttendu = $resultatAttendu;

        return $this;
    }

    public function isBesoinReference(): ?bool
    {
        return $this->besoinReference;
    }

    public function setBesoinReference(bool $besoinReference): static
    {
        $this->besoinReference = $besoinReference;

        return $this;
    }

    public function getDureeEstimee(): ?string
    {
        return $this->dureeEstimee;
    }

    public function setDureeEstimee(string $dureeEstimee): static
    {
        $this->dureeEstimee = $dureeEstimee;

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

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

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

    /**
     * @return Collection<int, Resultat>
     */
    public function getResultats(): Collection
    {
        return $this->resultats;
    }

    public function addResultat(Resultat $resultat): static
    {
        if (!$this->resultats->contains($resultat)) {
            $this->resultats->add($resultat);
            $resultat->setEtape($this);
        }

        return $this;
    }

    public function removeResultat(Resultat $resultat): static
    {
        if ($this->resultats->removeElement($resultat)) {
            // set the owning side to null (unless already changed)
            if ($resultat->getEtape() === $this) {
                $resultat->setEtape(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reference>
     */
    public function getParticipation(): Collection
    {
        return $this->participation;
    }

    public function addParticipation(Reference $participation): static
    {
        if (!$this->participation->contains($participation)) {
            $this->participation->add($participation);
            $participation->setEtape($this);
        }

        return $this;
    }

    public function removeParticipation(Reference $participation): static
    {
        if ($this->participation->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEtape() === $this) {
                $participation->setEtape(null);
            }
        }

        return $this;
    }
}
