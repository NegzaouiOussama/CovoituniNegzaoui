<?php

namespace App\Entity;

use App\Repository\ReponseAvisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReponseAvisRepository::class)]
class ReponseAvis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Avis::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Avis $avis = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $admin = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu de la réponse ne peut pas être vide')]
    #[Assert\Length(
        min: 2,
        max: 1000,
        minMessage: 'La réponse doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La réponse ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAvis(): ?Avis
    {
        return $this->avis;
    }

    public function setAvis(?Avis $avis): self
    {
        $this->avis = $avis;

        return $this;
    }

    public function getAdmin(): ?Utilisateur
    {
        return $this->admin;
    }

    public function setAdmin(?Utilisateur $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
} 