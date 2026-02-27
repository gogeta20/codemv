<?php

namespace App\Study\Application\Tag\Create;

use App\Study\Domain\Repository\TagRepositoryInterface;
use App\Study\Infrastructure\Doctrine\Entity\Tag;

final class CreateTagUseCase
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
    ) {}

    /** Idempotent: if tag already exists, does nothing. */
    public function execute(CreateTagCommand $command): void
    {
        $slug = strtolower(trim($command->slug));

        $existing = $this->tagRepository->findBySlug($slug);

        if ($existing !== null) {
            return;
        }

        $tag = new Tag($slug, $command->name ?? $slug);
        $this->tagRepository->save($tag);
    }
}
