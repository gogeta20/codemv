<?php

namespace App\Study\Application\Tag\Create;

final readonly class CreateTagCommand
{
    public function __construct(
        public string $slug,
        public ?string $name = null,
    ) {}
}
