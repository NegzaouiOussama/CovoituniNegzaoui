<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le sujet est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 255,
        minMessage: "Ce texte est trop court. Il doit contenir 5 caractères ou plus.",
        maxMessage: "Ce texte est trop long. Il doit contenir 255 caractères ou moins."
    )]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 5,
        minMessage: "Ce texte est trop court. Il doit contenir 5 caractères ou plus."
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'reclamations')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?Utilisateur $user = null;

    #[ORM\Column(length: 20, options: ["default" => "pending"])]
    #[Assert\NotBlank(message: "Le statut est obligatoire")]
    #[Assert\Choice(
        choices: ['pending', 'in_progress', 'resolved', 'rejected'],
        message: "Statut invalide. Les options disponibles sont: en attente, en cours, résolu, rejeté"
    )]
    // Note: All methods (getStatus/setStatus and getState/setState) use this property
    private ?string $status = "pending";

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\OneToMany(mappedBy: 'reclamation', targetEntity: Reponse::class, orphanRemoval: true)]
    private Collection $reponses;

    public function __construct()
    {
        // Initialiser la date avec la date actuelle lors de la création
        $this->date = new \DateTime();
        $this->status = 'pending';
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

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

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->status;
    }

    public function setState(string $state): static
    {
        $this->status = $state;

        return $this;
    }

    // Add alias methods for consistency
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        // Vérifier si la date est valide avant de l'assigner
        if ($date instanceof \DateTimeInterface) {
            $this->date = $date;
        } else {
            // Utiliser la date actuelle si la date fournie est invalide
            $this->date = new \DateTime();
        }

        return $this;
    }

    /**
     * @return Collection<int, Reponse>
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(Reponse $reponse): static
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses->add($reponse);
            $reponse->setReclamation($this);
        }

        return $this;
    }

    public function removeReponse(Reponse $reponse): static
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getReclamation() === $this) {
                $reponse->setReclamation(null);
            }
        }

        return $this;
    }
}