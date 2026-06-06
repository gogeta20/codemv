<?php

namespace App\Futbol\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'futbol_favoritos')]
#[ORM\UniqueConstraint(name: 'UNIQ_favoritos_team_liga', columns: ['espn_team_id', 'espn_liga_code'])]
#[ORM\HasLifecycleCallbacks]
class FutbolFavorito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 20)]
    private string $espnTeamId;

    #[ORM\Column(type: 'string', length: 20)]
    private string $espnLigaCode;

    #[ORM\Column(type: 'string', length: 100)]
    private string $teamName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $ligaNombre;

    #[ORM\Column(type: 'string', length: 50)]
    private string $pais;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $uuid,
        string $espnTeamId,
        string $espnLigaCode,
        string $teamName,
        string $ligaNombre,
        string $pais,
    ) {
        $this->uuid         = $uuid;
        $this->espnTeamId   = $espnTeamId;
        $this->espnLigaCode = $espnLigaCode;
        $this->teamName     = $teamName;
        $this->ligaNombre   = $ligaNombre;
        $this->pais         = $pais;
        $this->createdAt    = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getEspnTeamId(): string { return $this->espnTeamId; }
    public function getEspnLigaCode(): string { return $this->espnLigaCode; }
    public function getTeamName(): string { return $this->teamName; }
    public function getLigaNombre(): string { return $this->ligaNombre; }
    public function getPais(): string { return $this->pais; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function toArray(): array
    {
        return [
            'uuid'           => $this->uuid,
            'espn_team_id'   => $this->espnTeamId,
            'espn_liga_code' => $this->espnLigaCode,
            'team_name'      => $this->teamName,
            'liga_nombre'    => $this->ligaNombre,
            'pais'           => $this->pais,
            'created_at'     => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
