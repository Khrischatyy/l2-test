<?php

namespace App\Repository;

use App\Entity\ApiRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiRequest>
 *
 * @method ApiRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApiRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApiRequest[]    findAll()
 * @method ApiRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApiRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiRequest::class);
    }
} 