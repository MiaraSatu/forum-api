<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Doctrine\ORM\EntityManagerInterface;
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

    public function reactPost(int $postID, int $userID, bool $isLike, PostRepository $postRepo, UserRepository $userRepo): JsonResponse {
        if($post = $postRepo->find($postID)) {
            if($user = $userRepo->find($userID)) {
                $reaction = new Reaction;
                $reaction->setTargetType("post");
                $reaction->setTargetId($postID);
                // like for true and dislike for false
                $reaction->setIsLike($isLike);
                $reaction->setOwner($user);
                $this->em->persist($reaction);
                $this->em->flush();

                $jsonReaction = $this->serializer->serialize($reaction, 'json');

                return new JsonResponse($jsonReaction, Response::HTTP_CREATED, [], true);
            }
            return new JsonResponse(['message' => "User non trouvé"], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => "Post non trouvé"], Response::HTTP_NOT_FOUND);
    }
}
