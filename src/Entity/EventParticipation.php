<?php

namespace App\Entity;

use App\Repository\EventParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité qui représente la participation d'un utilisateur à un événement.
 * Cette participation peut être liée à une réservation de covoiturage acceptée
 * par un conducteur, auquel cas le conducteur est enregistré comme "parrain" 
 * de la participation.
 */
#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
#[ORM\Table(name: 'eventparticipation')]
class EventParticipation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_participation', type: 'integer')]
    private ?int $idParticipation = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'id_event', referencedColumnName: 'id_event', nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(name: 'date_inscription', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;
    
    #[ORM\Column(name: 'auteur', type: Types::STRING, length: 100, nullable: true)]
    private ?string $auteur = null;
    
    /**
     * Le conducteur qui a validé la participation de l'utilisateur à l'événement.
     * Ce champ est renseigné lorsqu'un passager réserve une place dans une annonce
     * d'événement et que le conducteur accepte cette réservation.
     * Il permet de garder une trace du "parrain" qui a facilité la participation.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_conducteur', referencedColumnName: 'id', nullable: true)]
    private ?Utilisateur $conducteur = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    public function getIdParticipation(): ?int
    {
        return $this->idParticipation;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }
    
    public function getAuteur(): ?string
    {
        return $this->auteur;
    }
    
    public function setAuteur(?string $auteur): static
    {
        $this->auteur = $auteur;
        
        return $this;
    }
    
    public function getConducteur(): ?Utilisateur
    {
        return $this->conducteur;
    }
    
    public function setConducteur(?Utilisateur $conducteur): static
    {
        $this->conducteur = $conducteur;
        
        return $this;
    }
}