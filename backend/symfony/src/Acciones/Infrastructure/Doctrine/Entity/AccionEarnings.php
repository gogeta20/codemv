<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acciones_earnings')]
#[ORM\UniqueConstraint(name: 'uniq_earnings_accion', columns: ['accion_id'])]
class AccionEarnings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Accion::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Accion $accion;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $earningsDate;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, nullable: true)]
    private ?string $epsEstimate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $fetchedAt;

    public function __construct(Accion $accion, \DateTimeImmutable $earningsDate, ?string $epsEstimate = null)
    {
        $this->accion       = $accion;
        $this->earningsDate = $earningsDate;
        $this->epsEstimate  = $epsEstimate;
        $this->fetchedAt    = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getAccion(): Accion { return $this->accion; }
    public function getEarningsDate(): \DateTimeImmutable { return $this->earningsDate; }
    public function getEpsEstimate(): ?string { return $this->epsEstimate; }
    public function getFetchedAt(): \DateTimeImmutable { return $this->fetchedAt; }

    public function setEarningsDate(\DateTimeImmutable $date): void { $this->earningsDate = $date; }
    public function setEpsEstimate(?string $eps): void { $this->epsEstimate = $eps; }
    public function setFetchedAt(\DateTimeImmutable $dt): void { $this->fetchedAt = $dt; }

    public function toArray(): array
    {
        return [
            'earnings_date' => $this->earningsDate->format('Y-m-d'),
            'eps_estimate'  => $this->epsEstimate !== null ? (float) $this->epsEstimate : null,
            'fetched_at'    => $this->fetchedAt->format('Y-m-d H:i:s'),
        ];
    }
}
