<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_copa_partidos')]
#[ORM\UniqueConstraint(name: 'uq_copa_competition_event', columns: ['competition_code', 'event_id'])]
#[ORM\Index(columns: ['fecha'], name: 'idx_copa_partido_fecha')]
#[ORM\Index(columns: ['competition_code'], name: 'idx_copa_partido_competition')]
#[ORM\HasLifecycleCallbacks]
class FutbolCopaPartido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'competition_code', type: 'string', length: 40)]
    private string $competitionCode;

    #[ORM\Column(name: 'event_id', type: 'string', length: 160)]
    private string $eventId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(name: 'hora_utc', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $horaUtc = null;

    #[ORM\Column(name: 'equipo_local', type: 'string', length: 120)]
    private string $equipoLocal;

    #[ORM\Column(name: 'equipo_visitante', type: 'string', length: 120)]
    private string $equipoVisitante;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $uuid,
        string $competitionCode,
        string $eventId,
        \DateTimeImmutable $fecha,
        string $equipoLocal,
        string $equipoVisitante,
        array $payload,
    ) {
        $this->uuid = $uuid;
        $this->competitionCode = $competitionCode;
        $this->eventId = $eventId;
        $this->fecha = $fecha;
        $this->equipoLocal = $equipoLocal;
        $this->equipoVisitante = $equipoVisitante;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
        $this->syncHoraUtcFromPayload();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getCompetitionCode(): string { return $this->competitionCode; }
    public function getEventId(): string { return $this->eventId; }
    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function getHoraUtc(): ?\DateTimeImmutable { return $this->horaUtc; }
    public function getEquipoLocal(): string { return $this->equipoLocal; }
    public function getEquipoVisitante(): string { return $this->equipoVisitante; }
    public function getPayload(): array { return $this->payload; }

    public function updateFromPayload(array $payload): void
    {
        $this->payload = $payload;
        $this->competitionCode = (string) ($payload['competition']['code'] ?? $this->competitionCode);
        $this->equipoLocal = (string) ($payload['local']['name'] ?? $this->equipoLocal);
        $this->equipoVisitante = (string) ($payload['visitante']['name'] ?? $this->equipoVisitante);
        $this->syncHoraUtcFromPayload();
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    private function syncHoraUtcFromPayload(): void
    {
        $date = $this->payload['date'] ?? null;
        if (!is_string($date) || trim($date) === '') {
            $this->horaUtc = null;
            return;
        }

        try {
            $this->horaUtc = new \DateTimeImmutable($date);
        } catch (\Throwable) {
            $this->horaUtc = null;
        }
    }
}
