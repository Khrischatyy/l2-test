<?php

namespace App\Service;

use App\Entity\ApiLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLogService
{
    private float $startTime;
    private ApiLog $currentLog;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        $this->startTime = microtime(true);
    }

    public function log(Request $request, mixed $responseData, int $statusCode): void
    {
        $log = new ApiLog();
        $log->setMethod($request->getMethod())
            ->setEndpoint($request->getPathInfo())
            ->setIpAddress($request->getClientIp())
            ->setRequestData([
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'query' => $request->query->all(),
                'body' => $this->getRequestBody($request)
            ])
            ->setStatusCode($statusCode)
            ->setResponseData($responseData)
            ->setProcessingTime(microtime(true) - $this->startTime);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        return array_map(function($header) use ($sensitiveHeaders) {
            $name = strtolower($header[0] ?? '');
            return in_array($name, $sensitiveHeaders) ? ['[REDACTED]'] : $header;
        }, $headers);
    }

    private function getRequestBody(Request $request): mixed
    {
        $content = $request->getContent();
        if (empty($content)) {
            return null;
        }
        return json_decode($content, true) ?? $content;
    }
} 