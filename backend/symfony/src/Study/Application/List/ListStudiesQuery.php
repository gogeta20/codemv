<?php

namespace App\Study\Application\List;

final readonly class ListStudiesQuery
{
    public function __construct(
        public ?string $category = null,
        public ?string $tags = null,
        public bool $favorite = false,
        public ?string $status = null,
    ) {}
}
