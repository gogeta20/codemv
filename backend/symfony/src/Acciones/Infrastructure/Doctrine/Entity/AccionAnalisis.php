<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acciones_analisis')]
#[ORM\Index(columns: ['accion_id'], name: 'idx_analisis_accion')]
#[ORM\HasLifecycleCallbacks]
class AccionAnalisis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: Accion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Accion $accion;

    #[ORM\Column(type: 'json')]
    private array $rawIncome = [];

    #[ORM\Column(type: 'json')]
    private array $rawBalance = [];

    #[ORM\Column(type: 'json')]
    private array $rawCashflow = [];

    #[ORM\Column(type: 'json')]
    private array $metrics = [];

    // comprar | vigilar | especulativo | evitar
    #[ORM\Column(type: 'string', length: 20)]
    private string $score;

    #[ORM\Column(type: 'json')]
    private array $scoreDetail = [];

    // dividend_aristocrat | growth | recovery_play | value_trap | null
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $investmentType = null;

    // etapa del retrato financiero
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $stage = null;

    // sub-tipo cuando score = especulativo: expansion | id_activo | pre_revenue
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $speculativeType = null;

    #[ORM\Column(type: 'text')]
    private string $portrait;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $priceAtAnalysis = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $uuid,
        Accion $accion,
        array $rawIncome,
        array $rawBalance,
        array $rawCashflow,
        array $metrics,
        string $score,
        array $scoreDetail,
        string $portrait,
    ) {
        $this->uuid        = $uuid;
        $this->accion      = $accion;
        $this->rawIncome   = $rawIncome;
        $this->rawBalance  = $rawBalance;
        $this->rawCashflow = $rawCashflow;
        $this->metrics     = $metrics;
        $this->score       = $score;
        $this->scoreDetail = $scoreDetail;
        $this->portrait    = $portrait;
        $this->createdAt   = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getAccion(): Accion { return $this->accion; }
    public function getRawIncome(): array { return $this->rawIncome; }
    public function getRawBalance(): array { return $this->rawBalance; }
    public function getRawCashflow(): array { return $this->rawCashflow; }
    public function getMetrics(): array { return $this->metrics; }
    public function getScore(): string { return $this->score; }
    public function getScoreDetail(): array { return $this->scoreDetail; }
    public function getInvestmentType(): ?string { return $this->investmentType; }
    public function getStage(): ?string { return $this->stage; }
    public function getSpeculativeType(): ?string { return $this->speculativeType; }
    public function getPortrait(): string { return $this->portrait; }
    public function getPriceAtAnalysis(): ?string { return $this->priceAtAnalysis; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setInvestmentType(?string $v): void { $this->investmentType = $v; }
    public function setStage(?string $v): void { $this->stage = $v; }
    public function setSpeculativeType(?string $v): void { $this->speculativeType = $v; }
    public function setPriceAtAnalysis(?string $v): void { $this->priceAtAnalysis = $v; }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'accion_uuid'      => $this->accion->getUuid(),
            'symbol'           => $this->accion->getSymbol(),
            'score'            => $this->score,
            'score_detail'     => $this->scoreDetail,
            'investment_type'  => $this->investmentType,
            'stage'            => $this->stage,
            'speculative_type' => $this->speculativeType,
            'portrait'         => $this->portrait,
            'metrics'          => $this->metrics,
            'price_at_analysis'=> $this->priceAtAnalysis ? (float) $this->priceAtAnalysis : null,
            'created_at'       => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
