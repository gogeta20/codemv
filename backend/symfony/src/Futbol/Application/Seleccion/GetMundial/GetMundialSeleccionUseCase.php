<?php

namespace App\Futbol\Application\Seleccion\GetMundial;

use App\Futbol\Domain\Repository\FutbolSeleccionDiariaRepositoryInterface;

final class GetMundialSeleccionUseCase
{
    public function __construct(
        private readonly FutbolSeleccionDiariaRepositoryInterface $seleccionRepository,
    ) {}

    public function execute(GetMundialSeleccionQuery $query): array
    {
        $fecha = $query->fecha ?? new \DateTimeImmutable('today');

        $top   = $this->seleccionRepository->findByFecha($fecha, 'mundial_top');
        $under = $this->seleccionRepository->findByFecha($fecha, 'mundial_under');

        return [
            'top'   => array_map(fn($s) => $s->toArray(), $top),
            'under' => array_map(fn($s) => $s->toArray(), $under),
        ];
    }
}
