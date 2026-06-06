<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_seleccion_diaria')]
#[ORM\UniqueConstraint(name: 'uq_seleccion_partido', columns: ['fecha', 'partido_id', 'tipo'])]
#[ORM\Index(columns: ['fecha'], name: 'idx_seleccion_fecha')]
class FutbolSeleccionDiaria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $fecha;

    #[ORM\ManyToOne(targetEntity: FutbolPartido::class)]
    #[ORM\JoinColumn(name: 'partido_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private FutbolPartido $partido;

    #[ORM\Column(type: 'string', length: 30, options: ['default' => 'con_temporada'])]
    private string $tipo = 'con_temporada'; // con_temporada | sin_temporada

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $posicion = 1; // rank 1-4 en la selección del día

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $razones = null; // breakdown de por qué fue elegido

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $uuid,
        \DateTimeImmutable $fecha,
        FutbolPartido $partido,
        int $posicion,
        string $tipo = 'con_temporada',
        ?array $razones = null,
    ) {
        $this->uuid      = $uuid;
        $this->fecha     = $fecha;
        $this->partido   = $partido;
        $this->posicion  = $posicion;
        $this->tipo      = $tipo;
        $this->razones   = $razones;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getFecha(): \DateTimeImmutable { return $this->fecha; }
    public function getPartido(): FutbolPartido { return $this->partido; }
    public function getPosicion(): int { return $this->posicion; }
    public function getTipo(): string { return $this->tipo; }
    public function getRazones(): ?array { return $this->razones; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'fecha'      => $this->fecha->format('Y-m-d'),
            'tipo'       => $this->tipo,
            'posicion'   => $this->posicion,
            'razones'    => $this->razones,
            'partido'    => $this->partido->toArray(),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
