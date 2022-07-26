<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentRepository;
use App\Service\SerializerService;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use App\Entity\Comment;

class CommentsController extends AbstractController
{
    private Serializer $serializer;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->serializer = SerializerService::getSerializer();
        $this->em = $em;
    }

    public function create(int $postID, Request $request, PostRepository $postRepo, UserRepository $userRepo, ValidatorInterface $validator): JsonResponse {
        if($post = $postRepo->find($postID)) {
            if($user = $this->getUser()) {
                $comment = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');
                // checking validation
                $errors = $validator->validate($comment);
                if($errors->count() > 0)
                    return new JsonResponse($this->serializer->serialize(compact('errors'), 'json'), Response::HTTP_BAD_REQUEST, [], true);

                $comment->setCommentedBy($user);
                $comment->setPost($post);
                $this->em->persist($comment);
                $this->em->flush();
                $jsonComment = $this->serializer->serialize($comment, 'json');

                return new JsonResponse($jsonComment, Response::HTTP_CREATED, [], true);
            }

            return new JsonResponse(['message' => 'User non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Post non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function pickAll(Request $request, CommentRepository $commentRepo, PaginationService $paginationService) {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $query = $commentRepo->getQueryBuilder([], [], ['createdAt'=>'DESC']);
        $paginator = $paginationService->getPaginator($request, $query, $page, $limit);
        // $comments = $commentRepo->findBy([], ['createdAt' => 'DESC']);
        // $jsonComments = $this->serializer->serialize($comments, 'json');
        $jsonPaginator = $this->serializer->serialize($paginator, 'json');

        return new JsonResponse($jsonPaginator, Response::HTTP_OK, [], true);
    }

    public function pick(int $commentID, CommentRepository $commentRepo): JsonResponse {
        if($comment = $commentRepo->find($commentID)) {
            $jsonComment = $this->serializer->serialize($comment, 'json');

            return new JsonResponse($jsonComment, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(['message'=>'Comment non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function update(int $commentID, Request $request, CommentRepository $commentRepo): JsonResponse {
        if($oldComment = $commentRepo->find($commentID)) {
            $comment = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');
            $oldComment->mergeWith($comment, ['content']);
            $this->em->flush();
            $jsonComment = $this->serializer->serialize($oldComment, 'json');

            return new JsonResponse($jsonComment, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(['message' => 'Comment non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function delete(int $commentID, CommentRepository $commentRepo): JsonResponse {
        if($comment = $commentRepo->find($commentID)) {
            $this->em->remove($comment);
            $this->em->flush();

            return new JsonResponse(['message' => 'Comment éffacé avec success!']);
        }

        return new JsonResponse(['message' => 'Comment non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function reply(int $commentID, Request $request, CommentRepository $commentRepo, UserRepository $userRepo): JsonResponse {
        if($parent = $commentRepo->find($commentID)) {
            if($user = $this->getUser()) {
                $response = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');
                $response->setParent($parent);
                $response->setPost($parent->getPost());
                $response->setCommentedBy($user);
                $this->em->persist($response);
                $this->em->flush();
                $jsonResponse = $this->serializer->serialize($response, 'json');

                return new JsonResponse($jsonResponse, Response::HTTP_CREATED, [], true);
            }

            return new JsonResponse(['message' => 'User non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Comment non trouvé'], Response::HTTP_NOT_FOUND);
    }
}
