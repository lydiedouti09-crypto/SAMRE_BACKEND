<?php

namespace App\Entity;

use App\Repository\MissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
#[ORM\Entity(repositoryClass: MissionRepository::class)]
class Mission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['mission:read', 'participation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]    
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $objectif = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $application = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $versionApplication = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $platforme = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $LienApplication = null;

    #[ORM\Column]
    #[Groups(['mission:read', 'participation:read'])]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column]
    #[Groups(['mission:read', 'participation:read'])]
    private ?\DateTime $DateFin = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $dureEstime = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $remuneration = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $ConditionsParticipation = null;

    #[ORM\Column]
    #[Groups(['mission:read', 'participation:read'])]
    private ?int $nombreParticipantsSouhaites = null;

    #[ORM\Column]
    #[Groups(['mission:read', 'participation:read'])]
    private ?int $nombreParticipantsActuels = null;

    #[ORM\Column(length: 255)]
    #[Groups(['mission:read', 'participation:read'])]
    private ?string $statut = null;

    #[ORM\Column]
    #[Groups(['mission:read',])]
    private ?\DateTime $dateCreation = null;

    /**
     * @var Collection<int, Etape>
     */
    #[ORM\OneToMany(targetEntity: Etape::class, mappedBy: 'mission')]
    #[Groups(['mission:read',])]
    private Collection $etapes;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'missions')]
    #[Groups(['mission:read',])]
    private Collection $tags;

    /**
     * @var Collection<int, Reference>
     */
    #[ORM\OneToMany(targetEntity: Reference::class, mappedBy: 'mission')]
    #[Groups(['mission:read',])]
    private Collection $participation;

    /**
     * @var Collection<int, Participation>
     */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'mission')]
    #[Groups(['mission:read',])]
    private Collection $participations;

    #[ORM\ManyToOne(inversedBy: 'missions')]
    #[Groups(['mission:read',])]
    private ?User $responsable = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'missions')]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['mission:read'])]
    private ?Application $applicationEntity = null;

    public function __construct()
    {
        $this->etapes = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->participation = new ArrayCollection();
        $this->participations = new ArrayCollection();
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

    public function getObjectif(): ?string
    {
        return $this->objectif;
    }

    public function setObjectif(string $objectif): static
    {
        $this->objectif = $objectif;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getApplication(): ?string
    {
        return $this->application;
    }

    public function setApplication(string $application): static
    {
        $this->application = $application;

        return $this;
    }

    public function getVersionApplication(): ?string
    {
        return $this->versionApplication;
    }

    public function setVersionApplication(string $versionApplication): static
    {
        $this->versionApplication = $versionApplication;

        return $this;
    }

    public function getPlatforme(): ?string
    {
        return $this->platforme;
    }

    public function setPlatforme(string $platforme): static
    {
        $this->platforme = $platforme;

        return $this;
    }

    public function getLienApplication(): ?string
    {
        return $this->LienApplication;
    }

    public function setLienApplication(string $LienApplication): static
    {
        $this->LienApplication = $LienApplication;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->DateFin;
    }

    public function setDateFin(\DateTime $DateFin): static
    {
        $this->DateFin = $DateFin;

        return $this;
    }

    public function getDureEstime(): ?string
    {
        return $this->dureEstime;
    }

    public function setDureEstime(string $dureEstime): static
    {
        $this->dureEstime = $dureEstime;

        return $this;
    }

    #[Groups(['mission:read', 'participation:read'])]
    public function getDuree(): int
    {
        $digits = preg_replace('/[^0-9]/', '', (string)$this->dureEstime);
        return $digits !== '' ? (int)$digits : 3;
    }

    public function getRemuneration(): ?string
    {
        return $this->remuneration;
    }

    public function setRemuneration(string $remuneration): static
    {
        $this->remuneration = $remuneration;

        return $this;
    }

    public function getConditionsParticipation(): ?string
    {
        return $this->ConditionsParticipation;
    }

    public function setConditionsParticipation(string $ConditionsParticipation): static
    {
        $this->ConditionsParticipation = $ConditionsParticipation;

        return $this;
    }

    public function getNombreParticipantsSouhaites(): ?int
    {
        return $this->nombreParticipantsSouhaites;
    }

    public function setNombreParticipantsSouhaites(int $nombreParticipantsSouhaites): static
    {
        $this->nombreParticipantsSouhaites = $nombreParticipantsSouhaites;

        return $this;
    }

    public function getNombreParticipantsActuels(): ?int
    {
        return $this->nombreParticipantsActuels;
    }

    public function setNombreParticipantsActuels(int $nombreParticipantsActuels): static
    {
        $this->nombreParticipantsActuels = $nombreParticipantsActuels;

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

    /**
     * @return Collection<int, Etape>
     */
    public function getEtapes(): Collection
    {
        return $this->etapes;
    }

    public function addEtape(Etape $etape): static
    {
        if (!$this->etapes->contains($etape)) {
            $this->etapes->add($etape);
            $etape->setMission($this);
        }

        return $this;
    }

    public function removeEtape(Etape $etape): static
    {
        if ($this->etapes->removeElement($etape)) {
            // set the owning side to null (unless already changed)
            if ($etape->getMission() === $this) {
                $etape->setMission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

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
            $participation->setMission($this);
        }

        return $this;
    }

    public function removeParticipation(Reference $participation): static
    {
        if ($this->participation->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getMission() === $this) {
                $participation->setMission(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getApplicationEntity(): ?Application
    {
        return $this->applicationEntity;
    }

    public function setApplicationEntity(?Application $applicationEntity): static
    {
        $this->applicationEntity = $applicationEntity;
        if ($applicationEntity !== null) {
            $this->application = $applicationEntity->getNom();
            if ($applicationEntity->getPlateforme()) {
                $this->platforme = $applicationEntity->getPlateforme();
            }
            if ($applicationEntity->getLienTelechargement()) {
                $this->LienApplication = $applicationEntity->getLienTelechargement();
            }
            if ($applicationEntity->getVersion()) {
                $this->versionApplication = $applicationEntity->getVersion();
            }
        }

        return $this;
    }
}
