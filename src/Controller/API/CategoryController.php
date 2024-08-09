<?php

namespace App\Controller\API;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class CategoryController extends AbstractController
{
    #[Route('/api/categories', name:'category.index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        return $this->json($categories, Response::HTTP_OK, [], [
            'groups' => 'categories.index'
        ]);
    }

    #[Route('/api/categories/{id}', name: 'category.show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category, Response::HTTP_OK, [], [
            'groups' => ['categories.index', 'categories.show']
        ]);
    }

    #[Route('/api/categories', name: 'category.post', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[isGranted('ROLE_EDIT_2', 'ROLE_ADMIN')]
    public function post(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $category = $serializer->deserialize($data, Category::class, 'json');

        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($category);
        $em->flush();

        return $this->json($category, Response::HTTP_CREATED, [], [
            'groups' => ['categories.post']
        ]);
    }

    #[Route('/api/categories/{id}', name:'category.update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[isGranted('ROLE_EDIT_2', 'ROLE_ADMIN')]
    public function update(Request $request, Category $category, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $updatedUser = $serializer->deserialize($data, Category::class, 'json');

        if ($updatedUser->getName()) {
            $category->setName($updatedUser->getName());
        }
        $category->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json($category, Response::HTTP_OK, [], [
            'groups' => ['categories.post', 'categories.update']
        ]);
    }

    #[Route('/api/categories/{id}', name: 'category.delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[isGranted('ROLE_EDIT_2', 'ROLE_ADMIN')]
    public function delete(Category $category, EntityManagerInterface $em): JsonResponse
    {
        $products = $category->getProducts();

        if ($products->count() > 0) {
            return $this->json([
                'error' => 'Cannot delete category because it is associated with products.'
            ], Response::HTTP_CONFLICT); // HTTP 409 Conflict
        }

        $em->remove($category);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}