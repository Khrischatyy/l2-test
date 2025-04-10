<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractApiController
{
    #[Route('/', name: 'app_default')]
    public function index(): JsonResponse
    {
        return $this->createApiResponse([
            'message' => 'Welcome to your new Symfony API!',
            'path' => 'src/Controller/DefaultController.php',
        ]);
    }
} 