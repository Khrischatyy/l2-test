<?php

namespace App\EventListener;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class ExceptionListener
{
    private bool $debug;

    public function __construct(string $environment)
    {
        $this->debug = $environment === 'dev';
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $response = null;

        $data = [
            'status' => 'error',
            'message' => $exception->getMessage(),
        ];

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof ValidationException) {
            $violations = [];
            foreach ($exception->getViolations() as $violation) {
                $violations[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }
            $data['violations'] = $violations;
            $statusCode = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        if ($this->debug) {
            $data['debug'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $this->getFormattedTrace($exception),
                'previous' => $this->getPreviousExceptionData($exception->getPrevious()),
            ];
        }

        $response = new JsonResponse($data, $statusCode);
        $event->setResponse($response);
    }

    private function getFormattedTrace(\Throwable $exception): array
    {
        $trace = [];
        foreach ($exception->getTrace() as $item) {
            $trace[] = [
                'file' => $item['file'] ?? '',
                'line' => $item['line'] ?? '',
                'function' => $item['function'] ?? '',
                'class' => $item['class'] ?? '',
                'type' => $item['type'] ?? '',
                'args' => $this->formatArgs($item['args'] ?? []),
            ];
        }
        return $trace;
    }

    private function formatArgs(array $args): array
    {
        return array_map(function ($arg) {
            if (is_object($arg)) {
                return sprintf('Object(%s)', get_class($arg));
            }
            if (is_array($arg)) {
                return sprintf('Array(%d)', count($arg));
            }
            if (is_string($arg)) {
                return sprintf('"%s"', $arg);
            }
            if (is_null($arg)) {
                return 'null';
            }
            if (is_bool($arg)) {
                return $arg ? 'true' : 'false';
            }
            return $arg;
        }, $args);
    }

    private function getPreviousExceptionData(?\Throwable $previous): ?array
    {
        if (!$previous) {
            return null;
        }

        return [
            'class' => get_class($previous),
            'message' => $previous->getMessage(),
            'file' => $previous->getFile(),
            'line' => $previous->getLine(),
            'trace' => $this->getFormattedTrace($previous),
        ];
    }
} 