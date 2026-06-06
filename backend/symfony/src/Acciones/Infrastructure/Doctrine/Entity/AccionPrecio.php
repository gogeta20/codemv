<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acciones_precios')]
#[ORM\UniqueConstraint(name: 'uq_accion_date', columns: ['accion_id', 'date'])]
#[ORM\Index(columns: ['date'], name: 'idx_precios_date')]
#[ORM\Index(columns: ['change_pct'], name: 'idx_precios_change')]
class AccionPrecio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: Accion::class, inversedBy: 'precios')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Accion $accion;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $priceOpen = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4)]
    private string $priceClose;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $priceHigh = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $priceLow = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $volume = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $prevClose = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 4, nullable: true)]
    private ?string $changePct = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $changeAmount = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $uuid, Accion $accion, \DateTimeImmutable $date, string $priceClose)
    {
        $this->uuid = $uuid;
        $this->accion = $accion;
        $this->date = $date;
        $this->priceClose = $priceClose;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getAccion(): Accion { return $this->accion; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function getPriceClose(): string { return $this->priceClose; }
    public function getChangePct(): ?string { return $this->changePct; }
    public function getChangeAmount(): ?string { return $this->changeAmount; }

    public function setPriceOpen(?string $v): void { $this->priceOpen = $v; }
    public function setPriceClose(string $v): void { $this->priceClose = $v; }
    public function setPriceHigh(?string $v): void { $this->priceHigh = $v; }
    public function setPriceLow(?string $v): void { $this->priceLow = $v; }
    public function setVolume(?string $v): void { $this->volume = $v; }
    public function setPrevClose(?string $v): void { $this->prevClose = $v; }
    public function setChangePct(?string $v): void { $this->changePct = $v; }
    public function setChangeAmount(?string $v): void { $this->changeAmount = $v; }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'accion_id'     => $this->accion->getUuid(),
            'symbol'        => $this->accion->getSymbol(),
            'date'          => $this->date->format('Y-m-d'),
            'price_open'    => $this->priceOpen !== null ? (float) $this->priceOpen : null,
            'price_close'   => (float) $this->priceClose,
            'price_high'    => $this->priceHigh !== null ? (float) $this->priceHigh : null,
            'price_low'     => $this->priceLow !== null ? (float) $this->priceLow : null,
            'volume'        => $this->volume !== null ? (int) $this->volume : null,
            'prev_close'    => $this->prevClose !== null ? (float) $this->prevClose : null,
            'change_pct'    => $this->changePct !== null ? (float) $this->changePct : null,
            'change_amount' => $this->changeAmount !== null ? (float) $this->changeAmount : null,
            'created_at'    => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
