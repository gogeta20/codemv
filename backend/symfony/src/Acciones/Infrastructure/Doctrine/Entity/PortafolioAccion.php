<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'portafolio_acciones')]
#[ORM\UniqueConstraint(name: 'uq_portafolio_accion', columns: ['portafolio_id', 'accion_id'])]
#[ORM\Index(columns: ['status'], name: 'idx_portafolio_acciones_status')]
#[ORM\HasLifecycleCallbacks]
class PortafolioAccion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: Portafolio::class, inversedBy: 'acciones')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Portafolio $portafolio;

    #[ORM\ManyToOne(targetEntity: Accion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Accion $accion;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'watchlist'])]
    private string $status = 'watchlist'; // watchlist | candidato | activo | descartado

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $precioReferencia = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $uuid, Portafolio $portafolio, Accion $accion, string $status = 'watchlist')
    {
        $this->uuid       = $uuid;
        $this->portafolio = $portafolio;
        $this->accion     = $accion;
        $this->status     = $status;
        $this->createdAt  = new \DateTimeImmutable();
        $this->updatedAt  = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getPortafolio(): Portafolio { return $this->portafolio; }
    public function getAccion(): Accion { return $this->accion; }
    public function getStatus(): string { return $this->status; }
    public function getPrecioReferencia(): ?string { return $this->precioReferencia; }
    public function getNotas(): ?string { return $this->notas; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setStatus(string $status): void { $this->status = $status; }
    public function setPrecioReferencia(?string $v): void { $this->precioReferencia = $v; }
    public function setNotas(?string $v): void { $this->notas = $v; }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'portafolio_uuid'   => $this->portafolio->getUuid(),
            'portafolio_nombre' => $this->portafolio->getNombre(),
            'accion'            => $this->accion->toArray(),
            'status'            => $this->status,
            'precio_referencia' => $this->precioReferencia !== null ? (float) $this->precioReferencia : null,
            'notas'             => $this->notas,
            'created_at'        => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'        => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
