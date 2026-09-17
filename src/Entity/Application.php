<?php

namespace App\Entity;

use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'application')]
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['application:read', 'mission:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $logo = null;

    #[ORM\Column(length: 100)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $plateforme = 'Android';

    #[ORM\Column(length: 50)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $version = '1.0.0';

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $lienTelechargement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['application:read'])]
    private ?string $developpeurNom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['application:read'])]
    private ?string $developpeurEmail = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Groups(['application:read', 'application:admin', 'mission:read'])]
    private ?string $apiKey = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Groups(['application:read', 'application:admin', 'mission:read'])]
    private ?string $tokenIntegration = null;

    #[ORM\Column]
    #[Groups(['application:read', 'mission:read'])]
    private ?int $dureeJoursDefaut = 12;

    #[ORM\Column]
    #[Groups(['application:read', 'mission:read'])]
    private ?int $nbMaxPanelistes = 12;

    #[ORM\Column(length: 50)]
    #[Groups(['application:read', 'mission:read'])]
    private ?string $statut = 'en_attente_integration'; // 'en_attente_integration', 'active', 'en_test', 'pause', 'archivee'

    #[ORM\Column]
    #[Groups(['application:read'])]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column]
    #[Groups(['application:read'])]
    private ?\DateTime $dateModification = null;

    /**
     * @var Collection<int, Mission>
     */
    #[ORM\OneToMany(targetEntity: Mission::class, mappedBy: 'applicationEntity')]
    private Collection $missions;

    public function __construct()
    {
        $this->missions = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->dateModification = new \DateTime();
        $this->apiKey = 'sk_app_' . bin2hex(random_bytes(16));
        $this->tokenIntegration = 'integ_' . bin2hex(random_bytes(12));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getPlateforme(): ?string
    {
        return $this->plateforme;
    }

    public function setPlateforme(string $plateforme): static
    {
        $this->plateforme = $plateforme;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getLienTelechargement(): ?string
    {
        return $this->lienTelechargement;
    }

    public function setLienTelechargement(?string $lienTelechargement): static
    {
        $this->lienTelechargement = $lienTelechargement;
        return $this;
    }

    public function getDeveloppeurNom(): ?string
    {
        return $this->developpeurNom;
    }

    public function setDeveloppeurNom(?string $developpeurNom): static
    {
        $this->developpeurNom = $developpeurNom;
        return $this;
    }

    public function getDeveloppeurEmail(): ?string
    {
        return $this->developpeurEmail;
    }

    public function setDeveloppeurEmail(?string $developpeurEmail): static
    {
        $this->developpeurEmail = $developpeurEmail;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function regenerateApiKey(): string
    {
        $this->apiKey = 'sk_app_' . bin2hex(random_bytes(16));
        $this->dateModification = new \DateTime();
        return $this->apiKey;
    }

    public function getTokenIntegration(): ?string
    {
        return $this->tokenIntegration;
    }

    public function setTokenIntegration(string $tokenIntegration): static
    {
        $this->tokenIntegration = $tokenIntegration;
        return $this;
    }

    public function getDureeJoursDefaut(): ?int
    {
        return $this->dureeJoursDefaut;
    }

    public function setDureeJoursDefaut(int $dureeJoursDefaut): static
    {
        $this->dureeJoursDefaut = $dureeJoursDefaut;
        return $this;
    }

    public function getNbMaxPanelistes(): ?int
    {
        return $this->nbMaxPanelistes;
    }

    public function setNbMaxPanelistes(int $nbMaxPanelistes): static
    {
        $this->nbMaxPanelistes = $nbMaxPanelistes;
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

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTime $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    /**
     * @return Collection<int, Mission>
     */
    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): static
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setApplicationEntity($this);
        }

        return $this;
    }

    public function removeMission(Mission $mission): static
    {
        if ($this->missions->removeElement($mission)) {
            // set the owning side to null (unless already changed)
            if ($mission->getApplicationEntity() === $this) {
                $mission->setApplicationEntity(null);
            }
        }

        return $this;
    }
}
