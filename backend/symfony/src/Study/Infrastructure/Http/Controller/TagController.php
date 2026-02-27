<?php

namespace App\Study\Infrastructure\Http\Controller;

use App\Study\Infrastructure\Doctrine\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tags')]
class TagController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tags = $this->em->getRepository(Tag::class)->findBy([], ['slug' => 'ASC']);

        return $this->json([
            'data' => array_map(fn(Tag $t) => $t->toArray(), $tags),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['slug'])) {
            return $this->json(['error' => 'slug is required'], Response::HTTP_BAD_REQUEST);
        }

        $slug = strtolower(trim($data['slug']));
        $existing = $this->em->getRepository(Tag::class)->findOneBy(['slug' => $slug]);
        if ($existing) {
            return $this->json(['data' => $existing->toArray()], Response::HTTP_OK);
        }

        $tag = new Tag($slug, $data['name'] ?? $slug);
        $this->em->persist($tag);
        $this->em->flush();

        return $this->json(['data' => $tag->toArray()], Response::HTTP_CREATED);
    }
}
