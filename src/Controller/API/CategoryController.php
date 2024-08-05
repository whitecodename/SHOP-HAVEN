<?php

namespace App\Controller\API;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
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

    #[Route('/api/categories', name: 'category.create', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $category = $serializer->deserialize($data, Category::class, 'json');

        $category->setCreatedAt(new \DateTimeImmutable());
        $category->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($category);
        $em->flush();

        return $this->json($category, Response::HTTP_CREATED);
    }

    #[Route('/api/categories/{id}', name:'category.update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Category $category, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $updatedUser = $serializer->deserialize($data, Category::class, 'json');

        if ($updatedUser->getName()) {
            $category->setName($updatedUser->getName());
        }
        $category->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json($category);
    }

    #[Route('/api/categories/{id}', name: 'category.delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Category $category, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($category);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}