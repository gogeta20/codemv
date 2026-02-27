<?php

namespace App\Study\Infrastructure\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'studies')]
#[ORM\Index(columns: ['category_id'], name: 'idx_studies_category')]
#[ORM\Index(columns: ['is_favorite'], name: 'idx_studies_favorite')]
#[ORM\Index(columns: ['status'], name: 'idx_studies_status')]
#[ORM\HasLifecycleCallbacks]
class Study
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'study_tags')]
    private Collection $tags;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isFavorite = false;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'draft'])]
    private string $status = 'draft';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct(string $uuid, string $title, string $content, Category $category, ?string $summary = null)
    {
        $this->uuid = $uuid;
        $this->title = $title;
        $this->content = $content;
        $this->category = $category;
        $this->summary = $summary;
        $this->tags = new ArrayCollection();
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
    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getSummary(): ?string { return $this->summary; }
    public function getCategory(): Category { return $this->category; }
    public function getTags(): Collection { return $this->tags; }
    public function isFavorite(): bool { return $this->isFavorite; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTime { return $this->updatedAt; }

    public function setTitle(string $title): void { $this->title = $title; }
    public function setContent(string $content): void { $this->content = $content; }
    public function setSummary(?string $summary): void { $this->summary = $summary; }
    public function setCategory(Category $category): void { $this->category = $category; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function toggleFavorite(): void { $this->isFavorite = !$this->isFavorite; }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function syncTags(array $tags): void
    {
        $this->tags->clear();
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'title'       => $this->title,
            'content'     => $this->content,
            'summary'     => $this->summary,
            'category'    => $this->category->toArray(),
            'tags'        => array_map(fn(Tag $t) => $t->toArray(), $this->tags->toArray()),
            'is_favorite' => $this->isFavorite,
            'status'      => $this->status,
            'created_at'  => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /** Returns compact markdown for Claude consumption (token efficient) */
    public function toContext(): string
    {
        $tags = implode(', ', array_map(fn(Tag $t) => $t->getSlug(), $this->tags->toArray()));
        $summary = $this->summary ? "\n> {$this->summary}\n" : '';

        return <<<MD
        # {$this->title}
        category: {$this->category->getSlug()} | tags: {$tags} | status: {$this->status}
        {$summary}
        {$this->content}
        MD;
    }
}
