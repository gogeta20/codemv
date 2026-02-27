<?php

namespace App\Study\Application\Update;

final readonly class UpdateStudyCommand
{
    public function __construct(
        public string $uuid,
        public ?string $title = null,
        public ?string $content = null,
        // Use $setSummary=true when summary is explicitly included in the request (even if null)
        public bool $setSummary = false,
        public ?string $summary = null,
        public ?string $category = null,
        public ?array $tags = null,
        public ?string $status = null,
    ) {}
}
