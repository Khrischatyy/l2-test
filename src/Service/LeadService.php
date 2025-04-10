<?php

namespace App\Service;

use App\Entity\Lead;
use App\Exception\ValidationException;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class LeadService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        private LeadRepository $leadRepository
    ) {
    }

    public function createLead(array $data): Lead
    {
        $this->logger->info('Creating new lead', ['data' => array_diff_key($data, ['dateOfBirth' => true])]);
        
        try {
            $lead = new Lead();
            $this->hydrateLead($lead, $data);
            
            // Validate the entity
            $violations = $this->validator->validate($lead);
            if (count($violations) > 0) {
                throw new ValidationException($violations);
            }

            // Use cache for rate limiting and duplicate prevention
            $cacheKey = 'lead_' . md5($lead->getEmail() . $lead->getPhone());
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                throw new \RuntimeException('Duplicate submission or rate limit exceeded');
            }
            
            $this->entityManager->beginTransaction();
            try {
                $this->entityManager->persist($lead);
                $this->entityManager->flush();
                
                // Cache the submission for rate limiting
                $item->set(true);
                $item->expiresAfter(60); // 1 minute rate limiting window
                $this->cache->save($item);
                
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }

            return $lead;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create lead', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getLeads(int $page, int $limit, string $sortBy, string $sortOrder): array
    {
        $cacheKey = sprintf('leads_page_%d_limit_%d_sort_%s_%s', $page, $limit, $sortBy, $sortOrder);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        $offset = ($page - 1) * $limit;
        $leads = $this->leadRepository->findBy(
            [],
            [$sortBy => $sortOrder],
            $limit,
            $offset
        );

        $total = $this->leadRepository->count([]);
        
        $result = [
            'data' => $leads,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]
        ];

        $item->set($result);
        $item->expiresAfter(300); // Cache for 5 minutes
        $this->cache->save($item);

        return $result;
    }

    private function hydrateLead(Lead $lead, array $data): void
    {
        $lead->setFirstName($data['firstName'] ?? '');
        $lead->setLastName($data['lastName'] ?? '');
        $lead->setEmail($data['email'] ?? '');
        $lead->setPhone($data['phone'] ?? '');
        
        if (!empty($data['dateOfBirth'])) {
            try {
                $dateOfBirth = new \DateTime($data['dateOfBirth']);
                $lead->setDateOfBirth($dateOfBirth);
            } catch (\Exception $e) {
                throw new ValidationException($this->validator->validate(null, null, ['date_format']));
            }
        }
        
        $lead->setAdditionalData($data['additionalData'] ?? []);
    }
} 