<?php

namespace App\Futbol\Application\Seleccion\GetDiaria;

use App\Futbol\Domain\Repository\FutbolSeleccionDiariaRepositoryInterface;

final class GetSeleccionDiariaUseCase
{
    public function __construct(
        private readonly FutbolSeleccionDiariaRepositoryInterface $seleccionRepository,
    ) {}

    public function execute(GetSeleccionDiariaQuery $query): array
    {
        $fecha = $query->fecha ?? new \DateTimeImmutable('today');

        $conTemporada  = $this->seleccionRepository->findByFecha($fecha, 'con_temporada');
        $sinTemporada  = $this->seleccionRepository->findByFecha($fecha, 'sin_temporada');
        $under         = $this->seleccionRepository->findByFecha($fecha, 'under');
        $favorito      = $this->seleccionRepository->findByFecha($fecha, 'favorito');

        return [
            'con_temporada' => array_map(fn($s) => $s->toArray(), $conTemporada),
            'sin_temporada' => array_map(fn($s) => $s->toArray(), $sinTemporada),
            'under'         => array_map(fn($s) => $s->toArray(), $under),
            'favorito'      => array_map(fn($s) => $s->toArray(), $favorito),
        ];
    }
}
