<?php

namespace App\Study\Application\Delete;

final readonly class DeleteStudyCommand
{
    public function __construct(
        public string $uuid,
    ) {}
}
