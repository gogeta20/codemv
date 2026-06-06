<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acciones')]
#[ORM\Index(columns: ['symbol'], name: 'idx_acciones_symbol')]
#[ORM\Index(columns: ['is_active'], name: 'idx_acciones_active')]
#[ORM\HasLifecycleCallbacks]
class Accion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $symbol;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 10)]
    private string $type; // crypto | stock | etf

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'manual'])]
    private string $source = 'manual'; // manual | discovered

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $exchange = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $industry = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => '5.00'])]
    private string $alertThresholdPct = '5.00';

    #[ORM\OneToMany(targetEntity: AccionPrecio::class, mappedBy: 'accion', cascade: ['persist'])]
    #[ORM\OrderBy(['date' => 'DESC'])]
    private Collection $precios;

    #[ORM\OneToMany(targetEntity: Portafolio::class, mappedBy: 'accion', cascade: ['persist'])]
    private Collection $portafolios;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $uuid, string $symbol, string $name, string $type, string $source = 'manual')
    {
        $this->uuid = $uuid;
        $this->symbol = strtoupper($symbol);
        $this->name = $name;
        $this->type = $type;
        $this->source = $source;
        $this->precios = new ArrayCollection();
        $this->portafolios = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getSymbol(): string { return $this->symbol; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getSource(): string { return $this->source; }
    public function isActive(): bool { return $this->isActive; }
    public function getAlertThresholdPct(): string { return $this->alertThresholdPct; }
    public function getExchange(): ?string { return $this->exchange; }
    public function getSector(): ?string { return $this->sector; }
    public function getIndustry(): ?string { return $this->industry; }
    public function getPrecios(): Collection { return $this->precios; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setName(string $name): void { $this->name = $name; }
    public function setType(string $type): void { $this->type = $type; }
    public function setIsActive(bool $isActive): void { $this->isActive = $isActive; }
    public function setAlertThresholdPct(string $pct): void { $this->alertThresholdPct = $pct; }
    public function setExchange(?string $exchange): void { $this->exchange = $exchange; }
    public function setSector(?string $sector): void { $this->sector = $sector; }
    public function setIndustry(?string $industry): void { $this->industry = $industry; }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'uuid'                 => $this->uuid,
            'symbol'               => $this->symbol,
            'name'                 => $this->name,
            'type'                 => $this->type,
            'source'               => $this->source,
            'is_active'            => $this->isActive,
            'alert_threshold_pct'  => (float) $this->alertThresholdPct,
            'exchange'             => $this->exchange,
            'sector'               => $this->sector,
            'industry'             => $this->industry,
            'created_at'           => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'           => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
