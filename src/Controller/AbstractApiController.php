<?php

namespace App\Controller;

use App\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiController extends AbstractController
{
    protected function validate($data, ValidatorInterface $validator): void
    {
        $violations = $validator->validate($data);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            throw new ValidationException('Validation failed', $errors);
        }
    }

    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $response = parent::json($data, $status, $headers, $context);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    protected function createApiResponse(
        $data = null,
        string $message = 'Success',
        int $status = 200,
        array $errors = []
    ): JsonResponse {
        $responseData = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($errors)) {
            $responseData['errors'] = $errors;
        }

        return $this->json($responseData, $status);
    }
} 