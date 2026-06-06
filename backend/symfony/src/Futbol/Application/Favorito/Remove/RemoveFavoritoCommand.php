<?php

namespace App\Futbol\Application\Favorito\Remove;

final class RemoveFavoritoCommand
{
    public function __construct(public readonly string $uuid) {}
}
