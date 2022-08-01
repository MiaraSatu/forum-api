<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReactionRepository;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\SerializerService;
use App\Repository\UserRepository;
use App\Entity\Reaction;

class ReactionsController extends AbstractController
{
    private Serializer $serializer;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->serializer = SerializerService::getSerializer();
        $this->em = $em;
    }

    public function reactPost(int $postID, int $userID, bool $isLike, PostRepository $postRepo, UserRepository $userRepo, ReactionRepository $reactionRepo): JsonResponse{
        if($post = $postRepo->find($postID)) {
            if($user = $userRepo->find($userID)) {
                if($reaction = $reactionRepo->findOneBy(['targetType' => 'post', 'targetId' => $postID, 'owner' => $user])) {
                    if($reaction->isLike() !== $isLike)
                        $reaction->setIsLike($isLike);
                }
                else {
                    $reaction = new Reaction;
                    $reaction->setTargetType("post");
                    $reaction->setTargetId($postID);
                    // like for true and dislike for false
                    $reaction->setIsLike($isLike);
                    $reaction->setOwner($user);
                    $this->em->persist($reaction);
                }
                $this->em->flush();

                $jsonReaction = $this->serializer->serialize($reaction, 'json');

                return new JsonResponse($jsonReaction, Response::HTTP_CREATED, [], true);
            }
            return new JsonResponse(['message' => "User non trouvé"], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => "Post non trouvé"], Response::HTTP_NOT_FOUND);
    }

    public function pickAll(ReactionRepository $reactionRepo): JsonResponse {
        $reactions = $reactionRepo->findAll();
        $jsonReactions = $this->serializer->serialize($reactions, 'json');

        return new JsonResponse($jsonReactions, Response::HTTP_OK, [], true);
    }

    public function delete(int $reactionID, ReactionRepository $reactionRepo): JsonResponse {
        if($reaction = $reactionRepo->find($reactionID)) {
            $this->em->remove($reaction);
            $this->em->flush();

            // reaction supprimé
            return new JsonResponse(['message' => "Reaction supprimé avec success"], Response::HTTP_OK);
        }

        // reaction non trouvé
        return new JsonResponse(['message' => "Reaction non trouvé"], Response::HTTP_NOT_FOUND);
    }

    public function update(int $reactionID, bool $isLike, ReactionRepository $reactionRepo): JsonResponse {
        if($reaction = $reactionRepo->find($reactionID)) {
            if($reaction->isLike() != $isLike) {
                $reaction->setIsLike($isLike);
            }
            $jsonReaction = $this->serializer->serialize($reaction, 'json');

            return new JsonResponse($jsonReaction, Response::HTTP_OK, [], true);
        }

        // si reaction non trouvé
        return new JsonResponse(['message' => 'Reaction non trouvé'], Response::HTTP_NOT_FOUND);
    }

    public function reactComment(int $commentID, int $userID, bool $isLike, CommentRepository $commentRepo, UserRepository $userRepo, ReactionRepository $reactionRepo) {
        if($comment = $commentRepo->find($commentID)) {
            if($user = $userRepo->find($userID)) {
                // already exist
                if($reaction = $reactionRepo->findOneBy(['targetType' => "comment", 'targetId' => $commentID, 'owner' => $user])) {
                    if($reaction->isLike() != $isLike)
                        $reaction->setIsLike($isLike);
                }
                else {
                    $reaction = new Reaction();
                    $reaction->setTargetType('comment');
                    $reaction->setTargetId($commentID);
                    // like for true and dislike for false
                    $reaction->setIsLike($isLike);
                    $reaction->setOwner($user);
                    $this->em->persist($reaction);
                }
                $this->em->flush();
                $jsonReaction = $this->serializer->serialize($reaction, 'json');

                return new JsonResponse($jsonReaction, Response::HTTP_CREATED, [], true);
            }

            // user non trouvé
            return new JsonResponse(['message' => "User non trouvé"], Response::HTTP_NOT_FOUND);
        }

        // comment non trouvé
        return new JsonResponse(['message' => "Comment non trouvé"], Response::HTTP_NOT_FOUND);
    }
}
