<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_ligas_gpm')]
class FutbolLigaGpm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(name: 'codigo_espn', type: 'string', length: 30, unique: true)]
    private string $codigoEspn;

    #[ORM\Column(type: 'string', length: 100)]
    private string $liga;

    #[ORM\Column(type: 'string', length: 80)]
    private string $pais;

    #[ORM\Column(type: 'smallint', options: ['default' => 1])]
    private int $tier = 1;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    private float $gpm;

    #[ORM\Column(type: 'integer')]
    private int $partidos;

    #[ORM\Column(type: 'smallint')]
    private int $equipos;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigoEspn,
            'liga'   => $this->liga,
            'pais'   => $this->pais,
            'tier'   => $this->tier,
            'gpm'    => (float) $this->gpm,
            'partidos' => $this->partidos,
            'equipos'  => $this->equipos,
            'updated_at' => $this->updatedAt->format('Y-m-d'),
        ];
    }
}
