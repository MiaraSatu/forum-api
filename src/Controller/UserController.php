<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;

class UserController extends AbstractController
{
    public function create(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        dd($user);
        return new JsonResponse([
            'message' => 'Welcome to api-test',
            'info' => 'now is '.date('H:i m-d')
        ]);
    }
}
