<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentRepository;
use App\Service\SerializerService;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Entity\Comment;

class CommentsController extends AbstractController
{
    private Serializer $serializer;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->serializer = SerializerService::getSerializer();
        $this->em = $em;
    }

    public function create(int $postID, int $userID, Request $request, PostRepository $postRepo, UserRepository $userRepo): JsonResponse {
        if($post = $postRepo->find($postID)) {
            if($user = $userRepo->find($userID)) {
                $comment = $this->serializer->deserialize($request->getContent(), Comment::class, 'json');
                $comment->setCommentedBy($user);
                $post->addComment($comment);
                $this->em->persist($comment);
                // dd($comment);
                $this->em->flush();
                $jsonComment = $this->serializer->serialize($comment, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['comments', 'postedBy']]);

                return new JsonResponse($jsonComment, Response::HTTP_CREATED, [], true);
            }

            return new JsonResponse(['message' => 'User non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Post non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function pickAll(CommentRepository $commentRepo) {
        $comments = $commentRepo->findBy([], ['createdAt' => 'DESC']);
        $jsonComments = $this->serializer->serialize($comments, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['comments', 'postedBy']]);

        return new JsonResponse($jsonComments, Response::HTTP_OK, [], true);
    }
}
