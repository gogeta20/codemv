<?php

namespace App\Futbol\Application\Copa\Import;

use App\Futbol\Application\Copa\Support\EuropeanCupEspnService;
use App\Futbol\Domain\Repository\FutbolCopaPartidoRepositoryInterface;
use App\Futbol\Infrastructure\Doctrine\Entity\FutbolCopaPartido;
use Symfony\Component\Uid\Uuid;

final class SyncCopasPartidosUseCase
{
    public function __construct(
        private readonly EuropeanCupEspnService $sourceService,
        private readonly FutbolCopaPartidoRepositoryInterface $repository,
    ) {}

    public function execute(\DateTimeImmutable $fecha): array
    {
        $partidos = $this->sourceService->listMatches($fecha);

        $this->repository->removeByFecha($fecha);

        $saved = 0;
        foreach ($partidos as $partido) {
            $payload = $this->enrichPayload($partido);
            $entity = new FutbolCopaPartido(
                Uuid::v7()->toRfc4122(),
                (string) ($payload['competition']['code'] ?? ''),
                (string) ($payload['event_id'] ?? ''),
                $fecha,
                (string) ($payload['local']['name'] ?? ''),
                (string) ($payload['visitante']['name'] ?? ''),
                $payload,
            );

            $this->repository->save($entity, false);
            $saved++;
        }

        $this->repository->flush();

        return [
            'fecha' => $fecha->format('Y-m-d'),
            'saved' => $saved,
        ];
    }

    private function enrichPayload(array $payload): array
    {
        if (!isset($payload['details']) || !is_array($payload['details'])) {
            $payload['details'] = [];
        }

        if (isset($payload['meta']['stage'])) {
            $payload['details']['stage'] = $payload['meta']['stage'];
        }

        if (isset($payload['meta']['path'])) {
            $payload['details']['path'] = $payload['meta']['path'];
        }

        if (isset($payload['meta']['source_url'])) {
            $payload['details']['source_url'] = $payload['meta']['source_url'];
        }

        return $payload;
    }
}
