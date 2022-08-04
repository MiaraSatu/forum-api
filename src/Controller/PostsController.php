<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SerializerService;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use App\Entity\Post;
use App\Entity\User;

class PostsController extends AbstractController
{
    private Serializer $serializer;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        // initialize serializer
        $this->serializer = SerializerService::getSerializer();
        $this->em = $em;
    }

    public function create(Request $request, UserRepository $userRepo, ValidatorInterface $validator): JsonResponse {
        // si l'auteur du post exist dans la base
        if($targetUser = $this->getUser()) {
            $post = $this->serializer->deserialize($request->getContent(), Post::class, 'json');
            // cheking validation
            $errors = $validator->validate($post);
            if($errors->count() > 0)
                return new JsonResponse($this->serializer->serialize(compact('errors'), 'json'), Response::HTTP_BAD_REQUEST, [], true);

            // si pas d'erreur
            $post->setPostedBy($targetUser);
            $this->em->persist($post);
            $this->em->flush();
            $jsonPost = $this->serializer->serialize($post, 'json');

            return new JsonResponse($jsonPost, Response::HTTP_CREATED, [], true);
        }

        return new JsonResponse(["message" => "Auteur non trouvé"], Response::HTTP_NOT_FOUND, []);
    }

    public function pickAll(PostRepository $postRepo): JsonResponse {
        $posts = $postRepo->findBy([], ['createdAt' => 'DESC']);
        $jsonPosts = $this->serializer->serialize($posts, 'json');

        return new JsonResponse($jsonPosts, Response::HTTP_OK, [], 'true');
    }

    public function pick(int $postID, PostRepository $postRepo): JsonResponse {
        if($post = $postRepo->find($postID)) {
            $jsonPost = $this->serializer->serialize($post, 'json');
            return new JsonResponse($jsonPost, Response::HTTP_OK, [], true);
        }
        
        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND, []);
    }

    public function update(int $postID, Request $request, PostRepository $postRepo): JsonResponse {
        if($oldPost = $postRepo->find($postID)) {
            $post = $this->serializer->deserialize($request->getContent(), Post::class, 'json');
            $oldPost->mergeWith($post, ['subject', 'title', 'content']);
            $this->em->flush();
            $jsonPost = $this->serializer->serialize($oldPost, 'json');

            return new JsonResponse($jsonPost, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function delete(int $postID, PostRepository $postRepo): JsonResponse {
        if($post = $postRepo->find($postID)) {
            $this->em->remove($post);
            $this->em->flush();

            return new JsonResponse(['message'=>'Post éffacé avec success'], Response::HTTP_OK);
        }

        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND);
    }
}
