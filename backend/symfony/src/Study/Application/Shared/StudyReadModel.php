<?php

namespace App\Study\Application\Shared;

use App\Study\Infrastructure\Doctrine\Entity\Study;
use App\Study\Infrastructure\Doctrine\Entity\Tag;

final readonly class StudyReadModel
{
    /**
     * @param array<string, mixed> $category
     * @param array<int, array<string, mixed>> $tags
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $title,
        public string $content,
        public ?string $summary,
        public array $category,
        public array $tags,
        public bool $isFavorite,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Study $study): self
    {
        return new self(
            id:         $study->getId(),
            uuid:       $study->getUuid(),
            title:      $study->getTitle(),
            content:    $study->getContent(),
            summary:    $study->getSummary(),
            category:   $study->getCategory()->toArray(),
            tags:       array_map(fn(Tag $t) => $t->toArray(), $study->getTags()->toArray()),
            isFavorite: $study->isFavorite(),
            status:     $study->getStatus(),
            createdAt:  $study->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt:  $study->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'title'       => $this->title,
            'content'     => $this->content,
            'summary'     => $this->summary,
            'category'    => $this->category,
            'tags'        => $this->tags,
            'is_favorite' => $this->isFavorite,
            'status'      => $this->status,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    /** Returns compact markdown for Claude consumption (token efficient) */
    public function toContext(): string
    {
        $tagSlugs = implode(', ', array_map(fn(array $t) => $t['slug'], $this->tags));
        $categorySlug = $this->category['slug'] ?? '';
        $summary = $this->summary ? "\n> {$this->summary}\n" : '';

        return <<<MD
        # {$this->title}
        category: {$categorySlug} | tags: {$tagSlugs} | status: {$this->status}
        {$summary}
        {$this->content}
        MD;
    }
}
