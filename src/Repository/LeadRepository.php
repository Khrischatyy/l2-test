<?php

namespace App\Repository;

use App\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    public function findByEmail(string $email): ?Lead
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByPhone(string $phone): ?Lead
    {
        return $this->findOneBy(['phone' => $phone]);
    }

    public function findRecentLeads(int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(Lead $lead, bool $flush = false): void
    {
        $this->getEntityManager()->persist($lead);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Lead $lead, bool $flush = false): void
    {
        $this->getEntityManager()->remove($lead);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 