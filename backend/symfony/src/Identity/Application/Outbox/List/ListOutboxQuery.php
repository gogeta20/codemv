<?php

namespace App\Identity\Application\Outbox\List;

final readonly class ListOutboxQuery
{
    public function __construct(
        public int $limit = 50,
    ) {}
}
