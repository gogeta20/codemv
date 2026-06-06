<?php

namespace App\Acciones\Infrastructure\Doctrine\Repository;

use App\Acciones\Domain\Repository\PortafolioRepositoryInterface;
use App\Acciones\Infrastructure\Doctrine\Entity\Portafolio;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePortafolioRepository implements PortafolioRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findByUuid(string $uuid): ?Portafolio
    {
        return $this->em->getRepository(Portafolio::class)->findOneBy(['uuid' => $uuid]);
    }

    public function findDefault(): ?Portafolio
    {
        return $this->em->getRepository(Portafolio::class)->findOneBy(['isDefault' => true]);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(Portafolio::class)->findBy([], ['isDefault' => 'DESC', 'createdAt' => 'ASC']);
    }

    public function clearDefault(): void
    {
        $this->em->createQueryBuilder()
            ->update(Portafolio::class, 'p')
            ->set('p.isDefault', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }

    public function save(Portafolio $portafolio): void
    {
        $this->em->persist($portafolio);
        $this->em->flush();
    }

    public function delete(Portafolio $portafolio): void
    {
        $this->em->remove($portafolio);
        $this->em->flush();
    }
}
