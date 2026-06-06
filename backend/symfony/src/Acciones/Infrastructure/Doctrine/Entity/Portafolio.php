<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'portafolios')]
#[ORM\HasLifecycleCallbacks]
class Portafolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nombre;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDefault = false;

    #[ORM\OneToMany(targetEntity: PortafolioAccion::class, mappedBy: 'portafolio', cascade: ['persist', 'remove'])]
    private Collection $acciones;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $uuid, string $nombre, ?string $descripcion = null, bool $isDefault = false)
    {
        $this->uuid        = $uuid;
        $this->nombre      = $nombre;
        $this->descripcion = $descripcion;
        $this->isDefault   = $isDefault;
        $this->acciones    = new ArrayCollection();
        $this->createdAt   = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getNombre(): string { return $this->nombre; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function isDefault(): bool { return $this->isDefault; }
    public function getAcciones(): Collection { return $this->acciones; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setDescripcion(?string $descripcion): void { $this->descripcion = $descripcion; }
    public function setIsDefault(bool $isDefault): void { $this->isDefault = $isDefault; }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_default'  => $this->isDefault,
            'total'       => $this->acciones->count(),
            'created_at'  => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
