<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Cache\ItemInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SerializerService;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use App\Service\PaginationService;
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

    public function create(Request $request, UserRepository $userRepo, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse {
        // si l'auteur du post exist dans la base
        if($targetUser = $this->getUser()) {
            $post = $this->serializer->deserialize($request->getContent(), Post::class, 'json');
            // cheking validation
            $errors = $validator->validate($post);
            if($errors->count() > 0)
                return new JsonResponse($this->serializer->serialize(compact('errors'), 'json'), Response::HTTP_BAD_REQUEST, [], true);

            // si pas d'erreur
            $post->setPostedBy($targetUser);
            $cachePool->invalidateTags(['postCache']);
            $this->em->persist($post);
            $this->em->flush();
            $jsonPost = $this->serializer->serialize($post, 'json');

            return new JsonResponse($jsonPost, Response::HTTP_CREATED, [], true);
        }

        return new JsonResponse(["message" => "Auteur non trouvé"], Response::HTTP_NOT_FOUND, []);
    }

    public function pickAll(Request $request, PostRepository $postRepo, PaginationService $paginationService, TagAwareCacheInterface $cachePool): JsonResponse {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        // mise en cache
        $idCache = "pickAllPosts-". $page ."-". $limit;
        $jsonPaginator = $cachePool->get($idCache, function(ItemInterface $item) use ($postRepo, $request, $paginationService, $page, $limit){
            $item->tag("postCache");
            $query = $postRepo->getQueryBuilder([], [], ['createdAt'=>'DESC']);
            return $this->serializer->serialize($paginationService->getPaginator($request, $query, $page, $limit), 'json');
        });

        // $query = $postRepo->getQueryBuilder([], [], ['createdAt'=>'DESC']);
        // $paginator = $paginationService->getPaginator($request, $query, $page, $limit);
        // $posts = $postRepo->findBy([], ['createdAt' => 'DESC']);
        // $jsonPosts = $this->serializer->serialize($posts, 'json');

        return new JsonResponse($jsonPaginator, Response::HTTP_OK, [], true);
    }

    public function pick(int $postID, PostRepository $postRepo): JsonResponse {
        if($post = $postRepo->find($postID)) {
            $jsonPost = $this->serializer->serialize($post, 'json');
            return new JsonResponse($jsonPost, Response::HTTP_OK, [], true);
        }
        
        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND, []);
    }

    public function update(int $postID, Request $request, PostRepository $postRepo, TagAwareCacheInterface $cachePool): JsonResponse {
        if($oldPost = $postRepo->find($postID)) {
            $post = $this->serializer->deserialize($request->getContent(), Post::class, 'json');
            $oldPost->mergeWith($post, ['subject', 'title', 'content']);
            $cachePool->invalidateTags('postCache');
            $this->em->flush();
            $jsonPost = $this->serializer->serialize($oldPost, 'json');

            return new JsonResponse($jsonPost, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function delete(int $postID, PostRepository $postRepo, TagAwareCacheInterface $cachePool): JsonResponse {
        if($post = $postRepo->find($postID)) {
            $cachePool->invalidateTags('postCache');
            $this->em->remove($post);
            $this->em->flush();

            return new JsonResponse(['message'=>'Post éffacé avec success'], Response::HTTP_OK);
        }

        return new JsonResponse(['message'=>'Post non trouvé'], Response::HTTP_NOT_FOUND);
    }
}
