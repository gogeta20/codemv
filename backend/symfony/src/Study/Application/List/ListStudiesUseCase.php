<?php

namespace App\Study\Application\List;

use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use App\Study\Infrastructure\Doctrine\Entity\Tag;

final class ListStudiesUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
    ) {}

    public function execute(ListStudiesQuery $query): array
    {
        $filters = [
            'category' => $query->category,
            'tags'     => $query->tags,
            'favorite' => $query->favorite,
            'status'   => $query->status,
        ];

        $studies = $this->studyRepository->findWithFilters($filters);

        return array_map(fn(Study $s) => $this->toListItem($s), $studies);
    }

    private function toListItem(Study $s): array
    {
        return [
            'uuid'        => $s->getUuid(),
            'title'       => $s->getTitle(),
            'summary'     => $s->getSummary(),
            'category'    => $s->getCategory()->getSlug(),
            'tags'        => array_map(fn(Tag $t) => $t->getSlug(), $s->getTags()->toArray()),
            'is_favorite' => $s->isFavorite(),
            'status'      => $s->getStatus(),
            'created_at'  => $s->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
