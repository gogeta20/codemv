<?php

namespace App\Study\Infrastructure\Http\Controller;

use App\Study\Infrastructure\Doctrine\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $categories = $this->em->getRepository(Category::class)->findBy([], ['name' => 'ASC']);

        return $this->json([
            'data' => array_map(fn(Category $c) => $c->toArray(), $categories),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['slug']) || empty($data['name'])) {
            return $this->json(['error' => 'slug and name are required'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->em->getRepository(Category::class)->findOneBy(['slug' => $data['slug']]);
        if ($existing) {
            return $this->json(['error' => 'Category already exists', 'data' => $existing->toArray()], Response::HTTP_CONFLICT);
        }

        $category = new Category($data['slug'], $data['name']);
        $this->em->persist($category);
        $this->em->flush();

        return $this->json(['data' => $category->toArray()], Response::HTTP_CREATED);
    }
}
