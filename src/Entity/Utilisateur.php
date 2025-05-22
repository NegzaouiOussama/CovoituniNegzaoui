<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: "Le nom d'utilisateur ne peut pas être vide")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: "Le nom d'utilisateur doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom d'utilisateur ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z0-9_.-]+$/",
        message: "Le nom d'utilisateur ne peut contenir que des lettres, des chiffres, des points, des tirets et des underscores"
    )]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s'-]+$/",
        message: "Le nom ne peut pas contenir des chiffres ou des caractères spéciaux"
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide")]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: "Le prénom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le prénom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s'-]+$/",
        message: "Le prénom ne peut pas contenir des chiffres ou des caractères spéciaux"
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le numéro de téléphone ne peut pas être vide")]
    #[Assert\Regex(
        pattern: "/^[2-9]\d{7}$/", 
        message: "Le numéro de téléphone doit être au format tunisien (8 chiffres commençant par 2-9)"
    )]
    private ?string $tel = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas un email valide.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "L'email ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe ne peut pas être vide")]
    #[Assert\Length(
        min: 8,
        minMessage: "Le mot de passe doit contenir au moins {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/",
        message: "Le mot de passe doit contenir au moins une lettre, un chiffre et un caractère spécial"
    )]
    private ?string $mdp = null;

    #[ORM\Column(name: 'role_code', length: 50)]
    private ?string $roleCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, options: ['default' => 5.00])]
    private ?string $rating = '5.00';

    #[ORM\Column(name: 'trips_count', options: ['default' => 0])]
    private ?int $tripsCount = 0;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'image_path', length: 255, nullable: true)]
    private ?string $imagePath = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reclamation::class, orphanRemoval: true)]
    private Collection $reclamations;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: EventParticipation::class, orphanRemoval: true)]
    private Collection $eventParticipations;

    #[ORM\ManyToOne(inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'role_code', referencedColumnName: 'code', nullable: false)]
    private ?Role $role = null;

    #[ORM\OneToMany(mappedBy: 'conducteur', targetEntity: EventParticipation::class)]
    private Collection $parrainages;

    public function __construct()
    {
        $this->reclamations = new ArrayCollection();
        $this->eventParticipations = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->parrainages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(string $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): static
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getRoleCode(): ?string
    {
        return $this->roleCode;
    }

    public function setRoleCode(string $roleCode): static
    {
        $this->roleCode = $roleCode;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [];
        
        // Add role based on roleCode
        if ($this->roleCode === 'ADMIN') {
            $roles[] = 'ROLE_ADMIN';
        } elseif ($this->roleCode === 'CONDUCTEUR') {
            $roles[] = 'ROLE_CONDUCTEUR';
        } elseif ($this->roleCode === 'PASSAGER') {
            $roles[] = 'ROLE_PASSAGER';
        }
        
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->mdp;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }

    public function setRating(string $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getTripsCount(): ?int
    {
        return $this->tripsCount;
    }

    public function setTripsCount(int $tripsCount): static
    {
        $this->tripsCount = $tripsCount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * Check if user is banned
     * This is a virtual property that doesn't exist in the database
     * 
     * Note: This will be overridden by the UserBanService
     */
    public function getIsBanned(): bool
    {
        return false;
    }

    /**
     * Set user banned status
     * This is a virtual property that doesn't exist in the database
     * 
     * Note: This will be overridden by the UserBanService
     */
    public function setIsBanned(bool $isBanned): static
    {
        // Handled by UserBanService
        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->reclamations->contains($reclamation)) {
            $this->reclamations->add($reclamation);
            $reclamation->setUser($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->reclamations->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getUser() === $this) {
                $reclamation->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getEventParticipations(): Collection
    {
        return $this->eventParticipations;
    }

    public function addEventParticipation(EventParticipation $eventParticipation): static
    {
        if (!$this->eventParticipations->contains($eventParticipation)) {
            $this->eventParticipations->add($eventParticipation);
            $eventParticipation->setUtilisateur($this);
        }

        return $this;
    }

    public function removeEventParticipation(EventParticipation $eventParticipation): static
    {
        if ($this->eventParticipations->removeElement($eventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($eventParticipation->getUtilisateur() === $this) {
                $eventParticipation->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getParrainages(): Collection
    {
        return $this->parrainages;
    }

    public function addParrainage(EventParticipation $eventParticipation): static
    {
        if (!$this->parrainages->contains($eventParticipation)) {
            $this->parrainages->add($eventParticipation);
            $eventParticipation->setConducteur($this);
        }

        return $this;
    }

    public function removeParrainage(EventParticipation $eventParticipation): static
    {
        if ($this->parrainages->removeElement($eventParticipation)) {
            // set the owning side to null (unless already changed)
            if ($eventParticipation->getConducteur() === $this) {
                $eventParticipation->setConducteur(null);
            }
        }

        return $this;
    }
} 