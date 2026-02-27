<?php

namespace App\Study\Application\Update;

use App\Study\Domain\Exception\StudyNotFoundException;
use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Tag;

final class UpdateStudyUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(UpdateStudyCommand $command): void
    {
        $study = $this->studyRepository->findByUuid($command->uuid);

        if ($study === null) {
            throw new StudyNotFoundException($command->uuid);
        }

        if ($command->title !== null) {
            $study->setTitle($command->title);
        }
        if ($command->content !== null) {
            $study->setContent($command->content);
        }
        if ($command->setSummary) {
            $study->setSummary($command->summary);
        }
        if ($command->status !== null) {
            $study->setStatus($command->status);
        }

        if ($command->category !== null) {
            $category = $this->categoryRepository->findBySlug($command->category);
            if ($category === null) {
                throw new \InvalidArgumentException("Category '{$command->category}' not found.");
            }
            $study->setCategory($category);
        }

        if ($command->tags !== null) {
            $tags = $this->resolveTags($command->tags);
            $study->syncTags($tags);
        }

        $this->studyRepository->save($study);
    }

    /** @return Tag[] */
    private function resolveTags(array $slugs): array
    {
        $tags = [];
        foreach ($slugs as $slug) {
            $slug = strtolower(trim($slug));
            $tag = $this->tagRepository->findBySlug($slug);
            if ($tag === null) {
                $tag = new Tag($slug, $slug);
                $this->tagRepository->save($tag);
            }
            $tags[] = $tag;
        }
        return $tags;
    }
}
