<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only handle API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $statusCode = 500;
        $data = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'status' => $statusCode,
            'detail' => $exception->getMessage(),
            'class' => get_class($exception),
        ];

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $data['status'] = $statusCode;
        } elseif ($exception instanceof AuthenticationException) {
            $statusCode = 401;
            $data['status'] = $statusCode;
            $data['title'] = 'Authentication Error';
        } elseif ($exception instanceof ValidationFailedException) {
            $statusCode = 400;
            $data['status'] = $statusCode;
            $data['title'] = 'Validation Error';
            $data['violations'] = [];
            
            foreach ($exception->getViolations() as $violation) {
                $data['violations'][] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
        } elseif ($exception instanceof NotNullConstraintViolationException) {
            $statusCode = 400;
            $data['status'] = $statusCode;
            $data['title'] = 'Database Constraint Error';
            $data['detail'] = 'A required field is missing or null';
        }

        if ($this->isDebugMode()) {
            $data['trace'] = $this->getFormattedTrace($exception);
        }

        $response = new JsonResponse($data, $statusCode);
        $response->headers->set('Content-Type', 'application/problem+json');
        $event->setResponse($response);
    }

    private function isDebugMode(): bool
    {
        return $_SERVER['APP_DEBUG'] ?? false;
    }

    private function getFormattedTrace(\Throwable $exception): array
    {
        $trace = [];
        foreach ($exception->getTrace() as $item) {
            $trace[] = [
                'namespace' => $item['class'] ?? '',
                'short_class' => $item['class'] ? (new \ReflectionClass($item['class']))->getShortName() : '',
                'class' => $item['class'] ?? '',
                'type' => $item['type'] ?? '',
                'function' => $item['function'] ?? '',
                'file' => $item['file'] ?? '',
                'line' => $item['line'] ?? '',
            ];
        }
        return $trace;
    }
} 