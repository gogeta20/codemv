<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_ligas')]
#[ORM\Index(columns: ['external_code'], name: 'idx_liga_codigo')]
#[ORM\Index(columns: ['activa'], name: 'idx_liga_activa')]
class FutbolLiga
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nombre;

    #[ORM\Column(name: 'external_code', type: 'string', length: 20, unique: true)]
    private string $codigoEspn;

    #[ORM\Column(type: 'string', length: 50)]
    private string $pais;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $division = 1;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $activa = true;

    #[ORM\OneToMany(targetEntity: FutbolPartido::class, mappedBy: 'liga', cascade: ['persist'])]
    private Collection $partidos;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $uuid, string $nombre, string $codigoEspn, string $pais, int $division = 1)
    {
        $this->uuid       = $uuid;
        $this->nombre     = $nombre;
        $this->codigoEspn = $codigoEspn;
        $this->pais       = $pais;
        $this->division   = $division;
        $this->partidos   = new ArrayCollection();
        $this->createdAt  = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getNombre(): string { return $this->nombre; }
    public function getCodigoEspn(): string { return $this->codigoEspn; }
    public function getPais(): string { return $this->pais; }
    public function getDivision(): int { return $this->division; }
    public function isActiva(): bool { return $this->activa; }
    public function getPartidos(): Collection { return $this->partidos; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function setActiva(bool $activa): void { $this->activa = $activa; }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'nombre'      => $this->nombre,
            'codigo_espn' => $this->codigoEspn,
            'pais'        => $this->pais,
            'division'    => $this->division,
            'activa'      => $this->activa,
            'created_at'  => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
