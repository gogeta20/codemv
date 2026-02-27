<?php

namespace App\Study\Application\Category\Create;

final readonly class CreateCategoryCommand
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}
}
