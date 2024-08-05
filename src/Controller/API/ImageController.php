<?php

namespace App\Controller\API;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ImageController extends AbstractController
{

    #[Route('/api/images', name: 'image.index', methods: ['GET'])]
    public function index(ImageRepository $imageRepository, UrlGeneratorInterface $urlGenerator): Response
    {
        $images = $imageRepository->findAll();

        if(empty($images)){
            return new Response('No images', Response::HTTP_NOT_FOUND);
        }

        $imagesData = array_map(function ($image) use ($urlGenerator) {
            // UrlGeneratorInterface must be implemented by an url generator
            $imageUrl = $urlGenerator->generate('image.show', ['id' => $image->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            // Return the image data including the URL
            return [
                'id' => $image->getId(),
                'url' => $imageUrl, // An extra attribute to redirect to the show method
            ];
        }, $images);

        return new JsonResponse($imagesData, Response::HTTP_OK);
    }

    #[Route('/api/images/{id}', name: 'image.show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ImageRepository $imageRepository): Response
    {
        $image = $imageRepository->find($id);

        if (!$image) {
            return new Response('Image not found', Response::HTTP_NOT_FOUND);
        }

        // Construct absolute URL
        $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/products/' . $image->getPath();

        if (!file_exists($imagePath)) {
            return new Response('File not found', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse(new File($imagePath));
    }

    #[Route('/api/products/{product_id}/images', name: 'image.upload', requirements: ['product_id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function upload(int $product_id, Request $request, ProductRepository $productRepository, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $product = $productRepository->find($product_id);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_BAD_REQUEST);
        }

        /** @var UploadedFile $uploadFile */
        $uploadFile = $request->files->get('thumbnail');

        if (!$uploadFile) {
            return new JsonResponse(['error' => 'No image file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $image = new Image();
        $image->setProduct($product);
        $image->setThumbnail($uploadFile);

        $errors = $validator->validate($image);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($image);
        $em->flush();

        return $this->json($image, Response::HTTP_CREATED, [], [
            'groups' => 'images.index'
        ]);
    }

    #[Route('/api/products/{product_id}/images/{id}', name:'image.update', requirements: ['product_id' => '\d+', 'id' => '\d+'], methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $product_id, Request $request, int $id, ProductRepository $productRepository, ImageRepository $imageRepository, EntityManagerInterface $em): JsonResponse
    {
        //dd($_FILES);

        $product = $productRepository->find($product_id);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], Response::HTTP_BAD_REQUEST);
        }

        $image = $imageRepository->find($id);
        if (!$image) {
            return new JsonResponse(['error' => 'Image not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var UploadedFile $uploadFile */
        $uploadFile = $request->files->get('thumbnail');

        if (!$uploadFile) {
            return new JsonResponse(['error' => 'No image file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $image->setThumbnail($uploadFile);

        $em->flush();

        return $this->json($image, Response::HTTP_OK, [], [
            'groups' => 'images.index'
        ]);
    }

    #[Route('/api/products/images/{id}', name: 'image.delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Image $image, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($image);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}