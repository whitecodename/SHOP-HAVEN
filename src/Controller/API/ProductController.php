<?php

namespace App\Controller\API;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/api/products', name:'product.index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        return $this->json($products, Response::HTTP_OK, [], [
            'groups' => 'products.index'
        ]);
    }


    #[Route('/api/products/{id}', name: 'product.show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Product $product, ImageRepository $imageRepository, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer): JsonResponse
    {
        $images = $imageRepository->findBy(['product' => $product]);

        $imagesData = array_map(function ($image) use ($urlGenerator) {
            // UrlGeneratorInterface must be implemented by an url generator
            $imageUrl = $urlGenerator->generate('image.show', ['id' => $image->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            // Return the image data including the URL
            return [
                'id' => $image->getId(),
                'url' => $imageUrl, // An extra attribute to redirect to the show method
            ];
        }, $images);

        $productData = $serializer->normalize($product, null, [
            'groups' => ['products.show'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['images']
        ]);

        // Add images data to the serialized product
        $productData['images'] = $imagesData;

        return $this->json($productData, Response::HTTP_OK, [], [
            'groups' => ['products.index', 'products.show']
        ]);
    }

    #[Route('/api/products', name: 'product.create', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, CategoryRepository $categoryRepository, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $dataArray = json_decode($data, true);
        $product = $serializer->deserialize($data, Product::class, 'json');

        // Handle the category validity
        if (isset($dataArray['category']['id'])) {
            $categoryId = $dataArray['category']['id'];
            $category = $categoryRepository->find($categoryId);

            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }

            $product->setCategory($category);
        } else {
            return new JsonResponse(['error' => 'Category must be specified'], Response::HTTP_BAD_REQUEST);
        }

        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($product);
        $em->flush();

        return $this->json($product, Response::HTTP_CREATED, [], [
            'groups' => 'products.post'
        ]);
    }

    #[Route('/api/products/{id}', name: 'product.update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Product $product, CategoryRepository $categoryRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->getContent();
        $updatedProduct = $serializer->deserialize($data, Product::class, 'json');

        if ($updatedProduct->getName()) {
            $product->setName($updatedProduct->getName());
        }
        if ($updatedProduct->getDescription()) {
            $product->setDescription($updatedProduct->getDescription());
        }
        if ($updatedProduct->getPrice()) {
            $product->setPrice($updatedProduct->getPrice());
        }
        if ($updatedProduct->getQuantity()) {
            $product->setQuantity($updatedProduct->getQuantity());
        }

        // Handle the category validity
        if (isset($dataArray['category']['id'])) {
            $categoryId = $dataArray['category']['id'];
            $category = $categoryRepository->find($categoryId);

            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }

            $product->setCategory($category);
        } else {
            return new JsonResponse(['error' => 'Category must be specified'], Response::HTTP_BAD_REQUEST);
        }

        $product->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => 'products.post'
        ]);
    }


    #[Route('/api/products/{id}', name: 'product.delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}