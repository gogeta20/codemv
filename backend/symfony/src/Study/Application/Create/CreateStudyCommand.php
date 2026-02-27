<?php

namespace App\Study\Application\Create;

final readonly class CreateStudyCommand
{
    public function __construct(
        public string $uuid,
        public string $title,
        public string $content,
        public string $category,
        public ?string $summary = null,
        public array $tags = [],
        public ?string $status = null,
    ) {}
}
