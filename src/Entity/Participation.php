<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['participation:read',])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['participation:read',])]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['participation:read',])]
    private ?bool $contratAccepte = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['participation:read',])]
    private ?\DateTime $dateAcceptation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['participation:read',])]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['participation:read',])]
    private ?\DateTime $dateFin = null;

    #[ORM\Column]
    #[Groups(['participation:read',])]
    private ?int $progression = null;

    #[ORM\Column]
    #[Groups(['participation:read',])]
    private ?int $etapesCompletees = null;

    #[ORM\Column]
    #[Groups(['participation:read',])]
    private ?int $etapesTotal = null;

    #[ORM\Column]
    #[Groups(['participation:read',])]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['participation:read', 'mission:read'])]
    private ?string $panelisteUid = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[Groups(['participation:read',])]
    private ?User $User = null;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'participation')]
    #[Groups(['participation:read',])]
    private Collection $commentaires;

    /**
     * @var Collection<int, Resultat>
     */
    #[ORM\OneToMany(targetEntity: Resultat::class, mappedBy: 'participation')]
    #[Groups(['participation:read',])]
    private Collection $resultats;

    /**
     * @var Collection<int, Reference>
     */
    #[ORM\OneToMany(targetEntity: Reference::class, mappedBy: 'participation')]
    #[Groups(['participation:read',])]
    private Collection $participation;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[Groups(['participation:read',])]
    private ?Mission $mission = null;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->resultats = new ArrayCollection();
        $this->participation = new ArrayCollection();
        $this->panelisteUid = 'TST-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    #[Groups(['participation:read'])]
    public function getStatut(): ?string
    {
        return $this->status;
    }

    public function setStatut(?string $statut): static
    {
        $this->status = $statut;

        return $this;
    }

    public function isContratAccepte(): ?bool
    {
        return $this->contratAccepte;
    }

    public function setContratAccepte(bool $contratAccepte): static
    {
        $this->contratAccepte = $contratAccepte;

        return $this;
    }

    public function getDateAcceptation(): ?\DateTime
    {
        return $this->dateAcceptation;
    }

    public function setDateAcceptation(?\DateTime $dateAcceptation): static
    {
        $this->dateAcceptation = $dateAcceptation;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getProgression(): ?int
    {
        return $this->progression;
    }

    public function setProgression(int $progression): static
    {
        $this->progression = $progression;

        return $this;
    }

    public function getEtapesCompletees(): ?int
    {
        return $this->etapesCompletees;
    }

    public function setEtapesCompletees(int $etapesCompletees): static
    {
        $this->etapesCompletees = $etapesCompletees;

        return $this;
    }

    public function getEtapesTotal(): ?int
    {
        return $this->etapesTotal;
    }

    public function setEtapesTotal(int $etapesTotal): static
    {
        $this->etapesTotal = $etapesTotal;

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

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->User;
    }

    public function setUtilisateur(?User $user): static
    {
        $this->User = $user;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setParticipation($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getParticipation() === $this) {
                $commentaire->setParticipation(null);
            }
        }

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
            $resultat->setParticipation($this);
        }

        return $this;
    }

    public function removeResultat(Resultat $resultat): static
    {
        if ($this->resultats->removeElement($resultat)) {
            // set the owning side to null (unless already changed)
            if ($resultat->getParticipation() === $this) {
                $resultat->setParticipation(null);
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
            $participation->setParticipation($this);
        }

        return $this;
    }

    public function removeParticipation(Reference $participation): static
    {
        if ($this->participation->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getParticipation() === $this) {
                $participation->setParticipation(null);
            }
        }

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

    public function getPanelisteUid(): ?string
    {
        return $this->panelisteUid;
    }

    public function setPanelisteUid(?string $panelisteUid): static
    {
        $this->panelisteUid = $panelisteUid;

        return $this;
    }
}
