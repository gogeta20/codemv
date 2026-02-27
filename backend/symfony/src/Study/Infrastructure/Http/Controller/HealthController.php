<?php

namespace App\Study\Infrastructure\Http\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        try {
            $this->em->getConnection()->executeQuery('SELECT 1');
            $db = 'ok';
        } catch (\Throwable) {
            $db = 'error';
        }

        return $this->json([
            'status' => $db === 'ok' ? 'ok' : 'degraded',
            'db'     => $db,
        ]);
    }
}
