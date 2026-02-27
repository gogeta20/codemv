<?php

namespace App\Study\Application\Create;

use App\Study\Domain\Repository\CategoryRepositoryInterface;
use App\Study\Domain\Repository\StudyRepositoryInterface;
use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Study;
use App\Study\Infrastructure\Doctrine\Entity\Tag;

final class CreateStudyUseCase
{
    public function __construct(
        private readonly StudyRepositoryInterface $studyRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(CreateStudyCommand $command): Study
    {
        $category = $this->categoryRepository->findBySlug($command->category);

        if ($category === null) {
            throw new \InvalidArgumentException("Category '{$command->category}' not found.");
        }

        $study = new Study($command->title, $command->content, $category, $command->summary);

        if ($command->status !== null) {
            $study->setStatus($command->status);
        }

        if (!empty($command->tags)) {
            $tags = $this->resolveTags($command->tags);
            $study->syncTags($tags);
        }

        $this->studyRepository->save($study);

        return $study;
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
