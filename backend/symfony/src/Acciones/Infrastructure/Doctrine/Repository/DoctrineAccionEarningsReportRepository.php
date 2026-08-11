<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\AccionEarningsReportRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Accion;
use App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAccionEarningsReportRepository implements AccionEarningsReportRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findLatestByAccionIds(array $accionIds): array
    {
        if (empty($accionIds)) {
            return [];
        }

        $rows = $this->em->createQuery(
            'SELECT r
             FROM App\Acciones\Infrastructure\Doctrine\Entity\AccionEarningsReport r
             WHERE r.accion IN (:ids)
             ORDER BY r.filingDate DESC, r.id DESC'
        )
        ->setParameter('ids', $accionIds)
        ->getResult();

        $map = [];
        foreach ($rows as $report) {
            $accionId = $report->getAccion()->getId();
            if (!isset($map[$accionId])) {
                $map[$accionId] = $report;
            }
        }

        return $map;
    }

    public function findLatestByAccion(Accion $accion): ?AccionEarningsReport
    {
        return $this->em->getRepository(AccionEarningsReport::class)->findOneBy(
            ['accion' => $accion],
            ['filingDate' => 'DESC', 'id' => 'DESC']
        );
    }

    public function findByAccionAndAccessionNumber(Accion $accion, string $accessionNumber): ?AccionEarningsReport
    {
        return $this->em->getRepository(AccionEarningsReport::class)->findOneBy([
            'accion' => $accion,
            'accessionNumber' => $accessionNumber,
        ]);
    }

    public function save(AccionEarningsReport $report): void
    {
        $this->em->persist($report);
        $this->em->flush();
    }
}
