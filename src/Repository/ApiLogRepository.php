<?php

namespace App\Repository;

use App\Entity\ApiLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApiLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiLog::class);
    }

    public function findSlowRequests(float $threshold = 1.0): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.processingTime > :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('l.processingTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFailedRequests(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.statusCode >= :errorCode')
            ->setParameter('errorCode', 400)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getRequestStats(): array
    {
        $qb = $this->createQueryBuilder('l');
        
        return [
            'total_requests' => $qb->select('COUNT(l.id)')->getQuery()->getSingleScalarResult(),
            'avg_processing_time' => $qb->select('AVG(l.processingTime)')->getQuery()->getSingleScalarResult(),
            'error_rate' => $qb->select('COUNT(l.id)')
                ->where('l.statusCode >= :errorCode')
                ->setParameter('errorCode', 400)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }
} 