<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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

    public function create(int $userID, Request $request, UserRepository $userRepo): JsonResponse {
        // si l'auteur du post exist dans la base
        if($targetUser = $userRepo->find($userID)) {
            $post = $this->serializer->deserialize($request->getContent(), Post::class, 'json');
            $targetUser->addPost($post);
            $this->em->persist($post);
            $this->em->flush();
            $jsonPost = $this->serializer->serialize($post, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['posts']]);

            return new JsonResponse($jsonPost, Response::HTTP_CREATED, [], true);
        }

        return new JsonResponse(["message" => "Auteur non trouvé"], Response::HTTP_NOT_FOUND, []);
    }

    public function pickAll(PostRepository $postRepo): JsonResponse {
        $posts = $postRepo->findBy([], ['createdAt' => 'DESC']);
        $jsonPosts = $this->serializer->serialize($posts, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['posts']]);

        return new JsonResponse($jsonPosts, Response::HTTP_OK, [], 'true');
    }

    public function pick(int $postID, PostRepository $postRepo): JsonResponse {
        if($post = $postRepo->find($postID)) {
            $jsonPost = $this->serializer->serialize($post, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['posts']]);
            return new JsonResponse($jsonPost, Response::HTTP_OK, [], true);
        }
        
        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND, []);
    }

    public function update(int $postID, Request $request, PostRepository $postRepo): JsonResponse {
        if($oldPost = $postRepo->find($postID)) {
            $post = $this->serializer->deserialize($request->getContent(), Post::class, 'json');
            $oldPost->mergeWith($post, ['subject', 'title', 'content']);
            $this->em->flush();
            $jsonPost = $this->serializer->serialize($oldPost, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['posts']]);

            return new JsonResponse($jsonPost, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND, []);
    }
}
