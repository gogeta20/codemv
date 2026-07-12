<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_partidos')]
#[ORM\UniqueConstraint(name: 'uq_espn_event', columns: ['espn_event_id'])]
#[ORM\Index(columns: ['fecha'], name: 'idx_partido_fecha')]
#[ORM\Index(columns: ['estado'], name: 'idx_partido_estado')]
#[ORM\HasLifecycleCallbacks]
class FutbolPartido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: FutbolLiga::class, inversedBy: 'partidos')]
    #[ORM\JoinColumn(name: 'liga_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private FutbolLiga $liga;

    #[ORM\Column(name: 'espn_event_id', type: 'string', length: 30)]
    private string $espnEventId;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(name: 'hora_utc', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $horaUtc = null;

    #[ORM\Column(name: 'equipo_local', type: 'string', length: 100)]
    private string $equipoLocal;

    #[ORM\Column(name: 'equipo_visitante', type: 'string', length: 100)]
    private string $equipoVisitante;

    #[ORM\Column(type: 'string', length: 150, nullable: true)]
    private ?string $estadio = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'programado'])]
    private string $estado = 'programado'; // programado | en_juego | finalizado

    // --- Contexto tabla ---
    #[ORM\Column(name: 'pos_local', type: 'integer', nullable: true)]
    private ?int $posLocal = null;

    #[ORM\Column(name: 'pos_visitante', type: 'integer', nullable: true)]
    private ?int $posVisitante = null;

    #[ORM\Column(name: 'pts_local', type: 'integer', nullable: true)]
    private ?int $ptsLocal = null;

    #[ORM\Column(name: 'pts_visitante', type: 'integer', nullable: true)]
    private ?int $ptsVisitante = null;

    #[ORM\Column(name: 'pj_local', type: 'integer', nullable: true)]
    private ?int $pjLocal = null;

    #[ORM\Column(name: 'pj_visitante', type: 'integer', nullable: true)]
    private ?int $pjVisitante = null;

    // --- Forma reciente (últimos 5: "W-D-L-W-W") ---
    #[ORM\Column(name: 'forma_local', type: 'string', length: 20, nullable: true)]
    private ?string $formaLocal = null;

    #[ORM\Column(name: 'forma_visitante', type: 'string', length: 20, nullable: true)]
    private ?string $formaVisitante = null;

    // --- Odds ---
    #[ORM\Column(name: 'odds_local', type: 'decimal', precision: 6, scale: 2, nullable: true)]
    private ?string $oddsLocal = null;

    #[ORM\Column(name: 'odds_empate', type: 'decimal', precision: 6, scale: 2, nullable: true)]
    private ?string $oddsEmpate = null;

    #[ORM\Column(name: 'odds_visitante', type: 'decimal', precision: 6, scale: 2, nullable: true)]
    private ?string $oddsVisitante = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $spread = null;

    #[ORM\Column(name: 'over_under', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $overUnder = null;

    // --- Pickcenter (probabilidades %) ---
    #[ORM\Column(name: 'prob_local', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $probLocal = null;

    #[ORM\Column(name: 'prob_empate', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $probEmpate = null;

    #[ORM\Column(name: 'prob_visitante', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $probVisitante = null;

    // --- Head to Head ---
    #[ORM\Column(name: 'h2h_ganados_local', type: 'integer', nullable: true)]
    private ?int $h2hGanadosLocal = null;

    #[ORM\Column(name: 'h2h_ganados_visitante', type: 'integer', nullable: true)]
    private ?int $h2hGanadosVisitante = null;

    #[ORM\Column(name: 'h2h_empates', type: 'integer', nullable: true)]
    private ?int $h2hEmpates = null;

    #[ORM\Column(name: 'h2h_detalle', type: 'json', nullable: true)]
    private ?array $h2hDetalle = null;

    // --- Score de analizabilidad ---
    #[ORM\Column(name: 'score_analisis', type: 'integer', options: ['default' => 0])]
    private int $scoreAnalisis = 0;

    #[ORM\Column(name: 'score_detalle', type: 'json', nullable: true)]
    private ?array $scoreDetalle = null;

    // --- Resultado (se rellena al día siguiente) ---
    #[ORM\Column(name: 'goles_local', type: 'integer', nullable: true)]
    private ?int $golesLocal = null;

    #[ORM\Column(name: 'goles_visitante', type: 'integer', nullable: true)]
    private ?int $golesVisitante = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $goleadores = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $lideres = null;

    #[ORM\Column(name: 'corners_local', type: 'json', nullable: true)]
    private ?array $cornersLocal = null;

    #[ORM\Column(name: 'corners_visitante', type: 'json', nullable: true)]
    private ?array $cornersVisitante = null;

    // --- Mundial 2026 ---
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $fase = null; // group_stage | round_of_16 | quarter_final | semi_final | final

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private ?string $grupo = null; // A | B | ... | L

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $uuid,
        FutbolLiga $liga,
        string $espnEventId,
        \DateTimeImmutable $fecha,
        string $equipoLocal,
        string $equipoVisitante,
    ) {
        $this->uuid            = $uuid;
        $this->liga            = $liga;
        $this->espnEventId     = $espnEventId;
        $this->fecha           = $fecha;
        $this->equipoLocal     = $equipoLocal;
        $this->equipoVisitante = $equipoVisitante;
        $this->createdAt       = new \DateTimeImmutable();
        $this->updatedAt       = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getLiga(): FutbolLiga { return $this->liga; }
    public function getEspnEventId(): string { return $this->espnEventId; }
    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function getHoraUtc(): ?\DateTimeImmutable { return $this->horaUtc; }
    public function getEquipoLocal(): string { return $this->equipoLocal; }
    public function getEquipoVisitante(): string { return $this->equipoVisitante; }
    public function getEstadio(): ?string { return $this->estadio; }
    public function getEstado(): string { return $this->estado; }
    public function getPosLocal(): ?int { return $this->posLocal; }
    public function getPosVisitante(): ?int { return $this->posVisitante; }
    public function getPtsLocal(): ?int { return $this->ptsLocal; }
    public function getPtsVisitante(): ?int { return $this->ptsVisitante; }
    public function getPjLocal(): ?int { return $this->pjLocal; }
    public function getPjVisitante(): ?int { return $this->pjVisitante; }
    public function getFormaLocal(): ?string { return $this->formaLocal; }
    public function getFormaVisitante(): ?string { return $this->formaVisitante; }
    public function getOddsLocal(): ?string { return $this->oddsLocal; }
    public function getOddsEmpate(): ?string { return $this->oddsEmpate; }
    public function getOddsVisitante(): ?string { return $this->oddsVisitante; }
    public function getSpread(): ?string { return $this->spread; }
    public function getOverUnder(): ?string { return $this->overUnder; }
    public function getProbLocal(): ?string { return $this->probLocal; }
    public function getProbEmpate(): ?string { return $this->probEmpate; }
    public function getProbVisitante(): ?string { return $this->probVisitante; }
    public function getH2hGanadosLocal(): ?int { return $this->h2hGanadosLocal; }
    public function getH2hGanadosVisitante(): ?int { return $this->h2hGanadosVisitante; }
    public function getH2hEmpates(): ?int { return $this->h2hEmpates; }
    public function getH2hDetalle(): ?array { return $this->h2hDetalle; }
    public function getScoreAnalisis(): int { return $this->scoreAnalisis; }
    public function getScoreDetalle(): ?array { return $this->scoreDetalle; }
    public function getGolesLocal(): ?int { return $this->golesLocal; }
    public function getGolesVisitante(): ?int { return $this->golesVisitante; }
    public function getGoleadores(): ?array { return $this->goleadores; }
    public function getLideres(): ?array { return $this->lideres; }
    public function getCornersLocal(): ?array { return $this->cornersLocal; }
    public function getCornersVisitante(): ?array { return $this->cornersVisitante; }
    public function getFase(): ?string { return $this->fase; }
    public function getGrupo(): ?string { return $this->grupo; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setHoraUtc(?\DateTimeImmutable $horaUtc): void { $this->horaUtc = $horaUtc; }
    public function setEstadio(?string $estadio): void { $this->estadio = $estadio; }
    public function setEstado(string $estado): void { $this->estado = $estado; }
    public function setPosLocal(?int $pos): void { $this->posLocal = $pos; }
    public function setPosVisitante(?int $pos): void { $this->posVisitante = $pos; }
    public function setPtsLocal(?int $pts): void { $this->ptsLocal = $pts; }
    public function setPtsVisitante(?int $pts): void { $this->ptsVisitante = $pts; }
    public function setPjLocal(?int $pj): void { $this->pjLocal = $pj; }
    public function setPjVisitante(?int $pj): void { $this->pjVisitante = $pj; }
    public function setFormaLocal(?string $forma): void { $this->formaLocal = $forma; }
    public function setFormaVisitante(?string $forma): void { $this->formaVisitante = $forma; }
    public function setOddsLocal(?string $odds): void { $this->oddsLocal = $odds; }
    public function setOddsEmpate(?string $odds): void { $this->oddsEmpate = $odds; }
    public function setOddsVisitante(?string $odds): void { $this->oddsVisitante = $odds; }
    public function setSpread(?string $spread): void { $this->spread = $spread; }
    public function setOverUnder(?string $ou): void { $this->overUnder = $ou; }
    public function setProbLocal(?string $prob): void { $this->probLocal = $prob; }
    public function setProbEmpate(?string $prob): void { $this->probEmpate = $prob; }
    public function setProbVisitante(?string $prob): void { $this->probVisitante = $prob; }
    public function setH2hGanadosLocal(?int $v): void { $this->h2hGanadosLocal = $v; }
    public function setH2hGanadosVisitante(?int $v): void { $this->h2hGanadosVisitante = $v; }
    public function setH2hEmpates(?int $v): void { $this->h2hEmpates = $v; }
    public function setH2hDetalle(?array $detalle): void { $this->h2hDetalle = $detalle; }
    public function setScoreAnalisis(int $score): void { $this->scoreAnalisis = $score; }
    public function setScoreDetalle(?array $detalle): void { $this->scoreDetalle = $detalle; }
    public function setGolesLocal(?int $goles): void { $this->golesLocal = $goles; }
    public function setGolesVisitante(?int $goles): void { $this->golesVisitante = $goles; }
    public function setGoleadores(?array $goleadores): void { $this->goleadores = $goleadores; }
    public function setLideres(?array $lideres): void { $this->lideres = $lideres; }
    public function setCornersLocal(?array $v): void { $this->cornersLocal = $v; }
    public function setCornersVisitante(?array $v): void { $this->cornersVisitante = $v; }
    public function setFase(?string $fase): void { $this->fase = $fase; }
    public function setGrupo(?string $grupo): void { $this->grupo = $grupo; }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'liga'             => $this->liga->toArray(),
            'espn_event_id'    => $this->espnEventId,
            'fecha'            => $this->fecha->format('Y-m-d'),
            'hora_utc'         => $this->horaUtc?->format('Y-m-d H:i:s'),
            'equipo_local'     => $this->equipoLocal,
            'equipo_visitante' => $this->equipoVisitante,
            'estadio'          => $this->estadio,
            'estado'           => $this->estado,
            'tabla'            => [
                'pos_local'      => $this->posLocal,
                'pos_visitante'  => $this->posVisitante,
                'pts_local'      => $this->ptsLocal,
                'pts_visitante'  => $this->ptsVisitante,
                'pj_local'       => $this->pjLocal,
                'pj_visitante'   => $this->pjVisitante,
            ],
            'forma'            => [
                'local'      => $this->formaLocal,
                'visitante'  => $this->formaVisitante,
            ],
            'odds'             => [
                'local'      => $this->oddsLocal !== null ? (float) $this->oddsLocal : null,
                'empate'     => $this->oddsEmpate !== null ? (float) $this->oddsEmpate : null,
                'visitante'  => $this->oddsVisitante !== null ? (float) $this->oddsVisitante : null,
                'spread'     => $this->spread !== null ? (float) $this->spread : null,
                'over_under' => $this->overUnder !== null ? (float) $this->overUnder : null,
            ],
            'probabilidades'   => [
                'local'     => $this->probLocal !== null ? (float) $this->probLocal : null,
                'empate'    => $this->probEmpate !== null ? (float) $this->probEmpate : null,
                'visitante' => $this->probVisitante !== null ? (float) $this->probVisitante : null,
            ],
            'h2h'              => [
                'ganados_local'      => $this->h2hGanadosLocal,
                'ganados_visitante'  => $this->h2hGanadosVisitante,
                'empates'            => $this->h2hEmpates,
                'detalle'            => $this->h2hDetalle,
            ],
            'score_analisis'   => $this->scoreAnalisis,
            'score_detalle'    => $this->scoreDetalle,
            'resultado'        => [
                'goles_local'      => $this->golesLocal,
                'goles_visitante'  => $this->golesVisitante,
                'goleadores'       => $this->goleadores,
            ],
            'lideres'          => $this->lideres,
            'corners'          => [
                'local'      => $this->cornersLocal,
                'visitante'  => $this->cornersVisitante,
            ],
            'fase'             => $this->fase,
            'grupo'            => $this->grupo,
            'created_at'       => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
