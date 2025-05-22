<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_event', type: 'integer')]
    private ?int $idEvent = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'id_type', referencedColumnName: 'id_type', nullable: false)]
    private ?TypeEvent $typeEvent = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(name: 'date_event', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateEvent = null;

    #[ORM\Column(name: 'heure_event', type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $heureEvent = null;

    #[ORM\Column(name: 'lieu', type: 'string', length: 255)]
    private ?string $lieu = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => 'ACTIVE'])]
    private ?string $status = 'ACTIVE';

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventParticipation::class, orphanRemoval: true)]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: AnnonceEvent::class, orphanRemoval: true)]
    private Collection $annonceEvents;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->annonceEvents = new ArrayCollection();
    }

    public function getIdEvent(): ?int
    {
        return $this->idEvent;
    }

    public function getTypeEvent(): ?TypeEvent
    {
        return $this->typeEvent;
    }

    public function setTypeEvent(?TypeEvent $typeEvent): static
    {
        $this->typeEvent = $typeEvent;

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

    public function getDateEvent(): ?\DateTimeInterface
    {
        return $this->dateEvent;
    }

    public function setDateEvent(\DateTimeInterface $dateEvent): static
    {
        $this->dateEvent = $dateEvent;

        return $this;
    }

    public function getHeureEvent(): ?\DateTimeInterface
    {
        return $this->heureEvent;
    }

    public function setHeureEvent(\DateTimeInterface $heureEvent): static
    {
        $this->heureEvent = $heureEvent;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, EventParticipation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(EventParticipation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvent($this);
        }

        return $this;
    }

    public function removeParticipation(EventParticipation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getEvent() === $this) {
                $participation->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AnnonceEvent>
     */
    public function getAnnonceEvents(): Collection
    {
        return $this->annonceEvents;
    }

    public function addAnnonceEvent(AnnonceEvent $annonceEvent): static
    {
        if (!$this->annonceEvents->contains($annonceEvent)) {
            $this->annonceEvents->add($annonceEvent);
            $annonceEvent->setEvent($this);
        }

        return $this;
    }

    public function removeAnnonceEvent(AnnonceEvent $annonceEvent): static
    {
        if ($this->annonceEvents->removeElement($annonceEvent)) {
            // set the owning side to null (unless already changed)
            if ($annonceEvent->getEvent() === $this) {
                $annonceEvent->setEvent(null);
            }
        }

        return $this;
    }
}