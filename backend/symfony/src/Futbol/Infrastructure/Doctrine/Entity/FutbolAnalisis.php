<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_analisis')]
#[ORM\HasLifecycleCallbacks]
class FutbolAnalisis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\OneToOne(targetEntity: FutbolPartido::class)]
    #[ORM\JoinColumn(name: 'partido_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private FutbolPartido $partido;

    // --- Predicciones ---
    #[ORM\Column(name: 'texto_libre', type: 'text', nullable: true)]
    private ?string $textoLibre = null;

    #[ORM\Column(name: 'pred_ganador', type: 'string', length: 20, nullable: true)]
    private ?string $predGanador = null; // local | visitante | empate

    #[ORM\Column(name: 'pred_goles_local', type: 'integer', nullable: true)]
    private ?int $predGolesLocal = null;

    #[ORM\Column(name: 'pred_goles_visitante', type: 'integer', nullable: true)]
    private ?int $predGolesVisitante = null;

    #[ORM\Column(name: 'pred_over_under', type: 'decimal', precision: 4, scale: 1, nullable: true)]
    private ?string $predOverUnder = null; // ej: 2.5 (predice over)

    #[ORM\Column(name: 'pred_corners', type: 'integer', nullable: true)]
    private ?int $predCorners = null; // corners mínimos esperados

    #[ORM\Column(name: 'pred_jugador_destacado', type: 'string', length: 100, nullable: true)]
    private ?string $predJugadorDestacado = null; // jugador más probable en gol/remate

    // --- Verificación (se rellena al día siguiente) ---
    #[ORM\Column(name: 'acierto_ganador', type: 'boolean', nullable: true)]
    private ?bool $aciertoGanador = null;

    #[ORM\Column(name: 'acierto_goles', type: 'boolean', nullable: true)]
    private ?bool $aciertoGoles = null;

    #[ORM\Column(name: 'acierto_corners', type: 'boolean', nullable: true)]
    private ?bool $aciertoCorners = null;

    #[ORM\Column(name: 'acierto_jugador', type: 'boolean', nullable: true)]
    private ?bool $aciertoJugador = null;

    #[ORM\Column(name: 'notas_resultado', type: 'text', nullable: true)]
    private ?string $notasResultado = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $uuid, FutbolPartido $partido)
    {
        $this->uuid      = $uuid;
        $this->partido   = $partido;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getPartido(): FutbolPartido { return $this->partido; }
    public function getTextoLibre(): ?string { return $this->textoLibre; }
    public function getPredGanador(): ?string { return $this->predGanador; }
    public function getPredGolesLocal(): ?int { return $this->predGolesLocal; }
    public function getPredGolesVisitante(): ?int { return $this->predGolesVisitante; }
    public function getPredOverUnder(): ?string { return $this->predOverUnder; }
    public function getPredCorners(): ?int { return $this->predCorners; }
    public function getPredJugadorDestacado(): ?string { return $this->predJugadorDestacado; }
    public function getAciertoGanador(): ?bool { return $this->aciertoGanador; }
    public function getAciertoGoles(): ?bool { return $this->aciertoGoles; }
    public function getAciertoCorners(): ?bool { return $this->aciertoCorners; }
    public function getAciertoJugador(): ?bool { return $this->aciertoJugador; }
    public function getNotasResultado(): ?string { return $this->notasResultado; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setTextoLibre(?string $texto): void { $this->textoLibre = $texto; }
    public function setPredGanador(?string $pred): void { $this->predGanador = $pred; }
    public function setPredGolesLocal(?int $goles): void { $this->predGolesLocal = $goles; }
    public function setPredGolesVisitante(?int $goles): void { $this->predGolesVisitante = $goles; }
    public function setPredOverUnder(?string $ou): void { $this->predOverUnder = $ou; }
    public function setPredCorners(?int $corners): void { $this->predCorners = $corners; }
    public function setPredJugadorDestacado(?string $jugador): void { $this->predJugadorDestacado = $jugador; }
    public function setAciertoGanador(?bool $acierto): void { $this->aciertoGanador = $acierto; }
    public function setAciertoGoles(?bool $acierto): void { $this->aciertoGoles = $acierto; }
    public function setAciertoCorners(?bool $acierto): void { $this->aciertoCorners = $acierto; }
    public function setAciertoJugador(?bool $acierto): void { $this->aciertoJugador = $acierto; }
    public function setNotasResultado(?string $notas): void { $this->notasResultado = $notas; }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'partido_id'            => $this->partido->getUuid(),
            'texto_libre'           => $this->textoLibre,
            'predicciones'          => [
                'ganador'           => $this->predGanador,
                'goles_local'       => $this->predGolesLocal,
                'goles_visitante'   => $this->predGolesVisitante,
                'over_under'        => $this->predOverUnder !== null ? (float) $this->predOverUnder : null,
                'corners'           => $this->predCorners,
                'jugador_destacado' => $this->predJugadorDestacado,
            ],
            'aciertos'              => [
                'ganador'  => $this->aciertoGanador,
                'goles'    => $this->aciertoGoles,
                'corners'  => $this->aciertoCorners,
                'jugador'  => $this->aciertoJugador,
            ],
            'notas_resultado'       => $this->notasResultado,
            'created_at'            => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'            => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
