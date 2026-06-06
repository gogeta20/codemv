<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acciones_noticias')]
#[ORM\Index(columns: ['accion_id', 'date'], name: 'idx_noticias_accion_date')]
class AccionNoticia
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

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 4)]
    private string $changePct;

    #[ORM\Column(type: 'string', length: 500)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $pubDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $agentAnalysis = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $uuid,
        Accion $accion,
        \DateTimeImmutable $date,
        string $changePct,
        string $title,
    ) {
        $this->uuid      = $uuid;
        $this->accion    = $accion;
        $this->date      = $date;
        $this->changePct = $changePct;
        $this->title     = $title;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUuid(): string { return $this->uuid; }
    public function getAccion(): Accion { return $this->accion; }
    public function getDate(): \DateTimeImmutable { return $this->date; }
    public function getChangePct(): string { return $this->changePct; }
    public function getTitle(): string { return $this->title; }
    public function getSummary(): ?string { return $this->summary; }
    public function getUrl(): ?string { return $this->url; }
    public function getPubDate(): ?\DateTimeImmutable { return $this->pubDate; }
    public function getAgentAnalysis(): ?string { return $this->agentAnalysis; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function setSummary(?string $v): void { $this->summary = $v; }
    public function setUrl(?string $v): void { $this->url = $v; }
    public function setPubDate(?\DateTimeImmutable $v): void { $this->pubDate = $v; }
    public function setAgentAnalysis(?string $v): void { $this->agentAnalysis = $v; }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'accion_uuid'    => $this->accion->getUuid(),
            'symbol'         => $this->accion->getSymbol(),
            'date'           => $this->date->format('Y-m-d'),
            'change_pct'     => (float) $this->changePct,
            'title'          => $this->title,
            'summary'        => $this->summary,
            'url'            => $this->url,
            'pub_date'       => $this->pubDate?->format('Y-m-d'),
            'agent_analysis' => $this->agentAnalysis,
            'created_at'     => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
