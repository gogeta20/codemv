<?php

namespace App\Acciones\Infrastructure\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'acciones_earnings_reports')]
#[ORM\UniqueConstraint(name: 'uniq_earnings_report_accion_accession', columns: ['accion_id', 'accession_number'])]
#[ORM\Index(columns: ['accion_id', 'filing_date'], name: 'idx_earnings_report_accion_filing')]
#[ORM\Index(columns: ['source', 'form_type'], name: 'idx_earnings_report_source_form')]
#[ORM\HasLifecycleCallbacks]
class AccionEarningsReport
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

    #[ORM\Column(type: 'string', length: 20)]
    private string $source;

    #[ORM\Column(type: 'string', length: 20)]
    private string $formType;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $accessionNumber = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $cik = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $filingDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $reportDate = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $primaryDocumentName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $exhibitDocumentName = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $filingUrl = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $indexUrl = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $exhibitUrl = null;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $periodicReportUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $items = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $contentFormat;

    #[ORM\Column(type: 'text')]
    private string $rawContent;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $fetchedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(
        string $uuid,
        Accion $accion,
        string $source,
        string $formType,
        string $contentFormat,
        string $rawContent,
    ) {
        $this->uuid = $uuid;
        $this->accion = $accion;
        $this->source = $source;
        $this->formType = $formType;
        $this->contentFormat = $contentFormat;
        $this->rawContent = $rawContent;
        $this->fetchedAt = new \DateTimeImmutable();
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
    public function getAccion(): Accion { return $this->accion; }
    public function getSource(): string { return $this->source; }
    public function getFormType(): string { return $this->formType; }
    public function getAccessionNumber(): ?string { return $this->accessionNumber; }
    public function getCik(): ?string { return $this->cik; }
    public function getFilingDate(): ?\DateTimeImmutable { return $this->filingDate; }
    public function getReportDate(): ?\DateTimeImmutable { return $this->reportDate; }
    public function getPrimaryDocumentName(): ?string { return $this->primaryDocumentName; }
    public function getExhibitDocumentName(): ?string { return $this->exhibitDocumentName; }
    public function getFilingUrl(): ?string { return $this->filingUrl; }
    public function getIndexUrl(): ?string { return $this->indexUrl; }
    public function getExhibitUrl(): ?string { return $this->exhibitUrl; }
    public function getPeriodicReportUrl(): ?string { return $this->periodicReportUrl; }
    public function getItems(): ?string { return $this->items; }
    public function getContentFormat(): string { return $this->contentFormat; }
    public function getRawContent(): string { return $this->rawContent; }
    public function getMetadata(): array { return $this->metadata; }
    public function getFetchedAt(): \DateTimeImmutable { return $this->fetchedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setAccesssionNumber(?string $accessionNumber): void { $this->accessionNumber = $accessionNumber; }
    public function setCik(?string $cik): void { $this->cik = $cik; }
    public function setFilingDate(?\DateTimeImmutable $filingDate): void { $this->filingDate = $filingDate; }
    public function setReportDate(?\DateTimeImmutable $reportDate): void { $this->reportDate = $reportDate; }
    public function setPrimaryDocumentName(?string $primaryDocumentName): void { $this->primaryDocumentName = $primaryDocumentName; }
    public function setExhibitDocumentName(?string $exhibitDocumentName): void { $this->exhibitDocumentName = $exhibitDocumentName; }
    public function setFilingUrl(?string $filingUrl): void { $this->filingUrl = $filingUrl; }
    public function setIndexUrl(?string $indexUrl): void { $this->indexUrl = $indexUrl; }
    public function setExhibitUrl(?string $exhibitUrl): void { $this->exhibitUrl = $exhibitUrl; }
    public function setPeriodicReportUrl(?string $periodicReportUrl): void { $this->periodicReportUrl = $periodicReportUrl; }
    public function setItems(?string $items): void { $this->items = $items; }
    public function setContentFormat(string $contentFormat): void { $this->contentFormat = $contentFormat; }
    public function setRawContent(string $rawContent): void { $this->rawContent = $rawContent; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }
    public function setFetchedAt(\DateTimeImmutable $fetchedAt): void { $this->fetchedAt = $fetchedAt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'accion_uuid' => $this->accion->getUuid(),
            'symbol' => $this->accion->getSymbol(),
            'source' => $this->source,
            'form_type' => $this->formType,
            'accession_number' => $this->accessionNumber,
            'cik' => $this->cik,
            'filing_date' => $this->filingDate?->format('Y-m-d'),
            'report_date' => $this->reportDate?->format('Y-m-d'),
            'primary_document_name' => $this->primaryDocumentName,
            'exhibit_document_name' => $this->exhibitDocumentName,
            'filing_url' => $this->filingUrl,
            'index_url' => $this->indexUrl,
            'exhibit_url' => $this->exhibitUrl,
            'periodic_report_url' => $this->periodicReportUrl,
            'items' => $this->items,
            'content_format' => $this->contentFormat,
            'raw_content' => $this->rawContent,
            'metadata' => $this->metadata,
            'fetched_at' => $this->fetchedAt->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
