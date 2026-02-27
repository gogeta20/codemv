<?php

namespace App\Study\Application\ToggleFavorite;

final readonly class ToggleFavoriteCommand
{
    public function __construct(
        public string $uuid,
    ) {}
}
