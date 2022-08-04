<?php

namespace App\Controller;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SerializerService;
use App\Repository\UserRepository;
use App\Entity\User;

class UsersController extends AbstractController
{
    private Serializer $serializer;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        // initialize serializer
        $this->serializer = SerializerService::getSerializer();
        $this->em = $em;
    }

    public function create(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $validator->validate($user);
        if($errors->count() > 0)
            return new JsonResponse($this->serializer->serialize(['errors'=>$errors], 'json'), Response::HTTP_BAD_REQUEST, [], true);

        if(null === $clearPassword = $request->toArray()['clearPassword']) 
            return new JsonResponse(['message'=>"Mot de passe ne doit pas etre vide"], Response::HTTP_BAD_REQUEST);

        // si pas d'erreur
        $user->setPassword($hasher->hashPassword($user, $clearPassword));
        $this->em->persist($user);
        $this->em->flush();
        $jsonUser = $this->serializer->serialize($user, 'json');

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }

    public function pickAll(UserRepository $userRepo): JsonResponse {
        $users = $userRepo->findAll();
        $usersList = $this->serializer->serialize($users, 'json');

        return new JsonResponse($usersList, Response::HTTP_OK, [], true);
    }

    public function pick(int $userID, UserRepository $userRepo): JsonResponse {
        if($user = $userRepo->find($userID)) {
            $jsonUser = $this->serializer->serialize($user, 'json');

            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(["message" => "User non trouvé"], Response::HTTP_NOT_FOUND);
    }

    public function update(int $userID, Request $request, UserRepository $userRepo): JsonResponse {
        if($oldUser = $userRepo->find($userID)) {
            $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
            $oldUser->mergeWith($user, ['fullName', 'email']);
            $this->em->flush();
            $jsonUser = $this->serializer->serialize($oldUser, 'json');

            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(['message' => "User non trouvé"], Response::HTTP_NOT_FOUND);
    }

    public function delete(UserRepository $userRepo): JsonResponse {
        if($user = $this->getUser()) {
            $this->em->remove($user);
            $this->em->flush();
            return new JsonResponse(["message" => "User supprimé avec success"], Response::HTTP_OK);
        }

        return new JsonResponse(['message' => "User non trouvé"], Response::HTTP_NOT_FOUND);
    }
}
