<?php

namespace App\Futbol\Application\Copa\List;

final readonly class GetCopasPartidosQuery
{
    public function __construct(public ?\DateTimeImmutable $fecha = null) {}
}
