<?php

namespace App\Entity;

use App\Repository\AnnonceEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnonceEventRepository::class)]
#[ORM\Table(name: 'annonce_event')]
class AnnonceEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'titre', type: 'string', length: 255)]
    private ?string $titre = null;

    #[ORM\Column(name: 'description', type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(name: 'departure_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departureDate = null;

    #[ORM\Column(name: 'date_publication', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(name: 'driver_id', type: 'integer')]
    private ?int $driverId = null;

    #[ORM\Column(name: 'car_id', type: 'integer')]
    private ?int $carId = null;

    #[ORM\Column(name: 'status', type: 'string', length: 10)]
    private ?string $status = null;

    #[ORM\Column(name: 'available_seats', type: 'integer')]
    private ?int $availableSeats = null;

    #[ORM\Column(name: 'date_termination', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateTermination = null;

    #[ORM\ManyToOne(inversedBy: 'annonceEvents')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id_event', nullable: false)]
    private ?Event $event = null;

    #[ORM\OneToMany(mappedBy: 'annonceEvent', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\Column(name: 'prix', type: 'decimal', precision: 10, scale: 2, options: ['default' => 0.00])]
    private ?float $prix = 0.00;

    #[ORM\Column(name: 'departure_point', type: 'string', length: 255)]
    private ?string $departurePoint = null;

    #[ORM\Column(name: 'arrival_point', type: 'string', length: 255)]
    private ?string $arrivalPoint = null;

    public function __construct()
    {
        $this->datePublication = new \DateTime();
        $this->reservations = new ArrayCollection();
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

    public function getDepartureDate(): ?\DateTimeInterface
    {
        return $this->departureDate;
    }

    public function setDepartureDate(?\DateTimeInterface $departureDate): static
    {
        $this->departureDate = $departureDate;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;

        return $this;
    }

    public function getDriverId(): ?int
    {
        return $this->driverId;
    }

    public function setDriverId(int $driverId): static
    {
        $this->driverId = $driverId;

        return $this;
    }

    public function getCarId(): ?int
    {
        return $this->carId;
    }

    public function setCarId(int $carId): static
    {
        $this->carId = $carId;

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

    public function getAvailableSeats(): ?int
    {
        return $this->availableSeats;
    }

    public function setAvailableSeats(int $availableSeats): static
    {
        $this->availableSeats = $availableSeats;

        return $this;
    }

    public function getDateTermination(): ?\DateTimeInterface
    {
        return $this->dateTermination;
    }

    public function setDateTermination(?\DateTimeInterface $dateTermination): static
    {
        $this->dateTermination = $dateTermination;

        return $this;
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDeparturePoint(): ?string
    {
        return $this->departurePoint;
    }

    public function setDeparturePoint(string $departurePoint): static
    {
        $this->departurePoint = $departurePoint;

        return $this;
    }

    public function getArrivalPoint(): ?string
    {
        return $this->arrivalPoint;
    }

    public function setArrivalPoint(string $arrivalPoint): static
    {
        $this->arrivalPoint = $arrivalPoint;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setAnnonceEvent($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getAnnonceEvent() === $this) {
                $reservation->setAnnonceEvent(null);
            }
        }

        return $this;
    }
} 