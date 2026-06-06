<?php

namespace App\Futbol\Application\Liga\GetLigasGpm;

use App\Futbol\Domain\Repository\FutbolLigaGpmRepositoryInterface;

final class GetLigasGpmUseCase
{
    public function __construct(
        private readonly FutbolLigaGpmRepositoryInterface $repository,
    ) {}

    public function execute(): array
    {
        $ligas = $this->repository->findAllOrdenadas();

        if (empty($ligas)) {
            return ['mas_goles' => [], 'menos_goles' => [], 'todas' => [], 'total' => 0, 'actualizado' => null];
        }

        $todas = array_map(fn($l) => $l->toArray(), $ligas);

        $updatedAt = $ligas[0]->toArray()['updated_at'];

        return [
            'mas_goles'   => array_slice($todas, 0, 5),
            'menos_goles' => array_slice(array_reverse($todas), 0, 5),
            'todas'       => $todas,
            'total'       => count($todas),
            'actualizado' => $updatedAt,
        ];
    }
}
