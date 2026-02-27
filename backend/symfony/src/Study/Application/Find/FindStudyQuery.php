<?php

namespace App\Study\Application\Find;

final readonly class FindStudyQuery
{
    public function __construct(
        public string $uuid,
    ) {}
}
