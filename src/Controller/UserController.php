<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to api-test',
            'info' => 'now is '.date('H:i m-d')
        ]);
    }
}
