<?php

namespace App\Controller\API;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/api/products', name:'product.index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $categoryId = $request->query->get('category');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $minQuantity = $request->query->get('minQuantity');
        $maxQuantity = $request->query->get('maxQuantity');

        $criteria = [];
        if ($categoryId !== null) {
            $categoryId = (int)$categoryId;  // Convertir en entier
            if ($categoryId > 0) {           // Assurer que la valeur est valide
                $criteria['category'] = $categoryId;
            }
        }

        // Application des filtres pour le prix
        if ($minPrice !== null) {
            $minPrice = (float)$minPrice;  // Convertir en flottant
            $criteria['price']['>='] = $minPrice;
        }
        if ($maxPrice !== null) {
            $maxPrice = (float)$maxPrice;  // Convertir en flottant
            $criteria['price']['<='] = $maxPrice;
        }

        // Application des filtres pour la quantité
        if ($minQuantity !== null) {
            $minQuantity = (int)$minQuantity;  // Convertir en entier
            $criteria['quantity']['>='] = $minQuantity;
        }
        if ($maxQuantity !== null) {
            $maxQuantity = (int)$maxQuantity;  // Convertir en entier
            $criteria['quantity']['<='] = $maxQuantity;
        }

        $products = $productRepository->findByCriteria($criteria);

        return $this->json($products, Response::HTTP_OK, [], [
            'groups' => 'product.index'
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
            'groups' => ['product.show'],
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['images']
        ]);

        // Add images data to the serialized product
        $productData['images'] = $imagesData;

        return $this->json($productData, Response::HTTP_OK, [], [
            'groups' => ['product.index', 'product.show']
        ]);
    }

    #[Route('/api/products', name: 'product.create', requirements: ['id' => '\d+'], methods: ['POST'])]
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
            'groups' => 'product.post'
        ]);
    }

    #[Route('/api/products/{id}', name: 'product.update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(Request $request, Product $product, CategoryRepository $categoryRepository, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->getContent();
        $dataArray = json_decode($data, true);
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
            'groups' => 'product.post'
        ]);
    }


    #[Route('/api/products/{id}', name: 'product.delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Product $product, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($product);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}