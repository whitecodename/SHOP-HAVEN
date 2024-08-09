<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name:'user.index', methods: ['GET'])]
    public function index(userRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        return $this->json($users, 200, [], [
            'groups' => 'user.index'
        ]);
    }

    #[Route('/api/users/{id}', name: 'user.show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($user, 200, [], [
            'groups' => 'user.index'
        ]);
    }

    #[Route('/api/register', name: 'user.register', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->getContent();
        $user = $serializer->deserialize($data, User::class, 'json');

        $user->setPassword($hasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdatedAt(new \DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], [
            'groups' => 'user.register'
        ]);
    }

    #[Route('/api/users/{id}', name:'user.update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[isGranted('ROLE_USER')]
    public function update(Request $request, User $user, SerializerInterface $serializer, UserPasswordHasherInterface $hasher, EntityManagerInterface $em, Security $security): JsonResponse
    {
        $data = $request->getContent();
        $updatedUser = $serializer->deserialize($data, User::class, 'json');

        // Get the currently authenticated user
        $currentUser = $security->getUser();

        // Check if the current user is the same as the user to be deleted
        if ($currentUser === $user) {
            if ($updatedUser->getUsername()) {
                $user->setUsername($updatedUser->getUsername());
            }
            if ($updatedUser->getEmail()) {
                $user->setEmail($updatedUser->getEmail());
            }
            if ($updatedUser->getPassword()) {
                $user->setPassword($hasher->hashPassword($user, $updatedUser->getPassword()));
            }
        } else {
            if ($updatedUser->getRoles()) {
                $user->setRoles($updatedUser->getRoles());
            }
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], [
            'groups' => 'user.show'
        ]);
    }

    #[Route('/api/users/{id}', name: 'user.delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[isGranted('ROLE_USER')]
    public function delete(User $user, EntityManagerInterface $em, Security $security): JsonResponse
    {
        // Get the currently authenticated user
        $currentUser = $security->getUser();

        // Check if the current user is the same as the user to be deleted
        if ($currentUser !== $user) {
            return $this->json(['error' => 'You can only delete your own account.'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

}