<?php

namespace App\Service;

use App\Entity\Lead;
use App\Exception\ValidationException;
use App\Exception\DuplicateLeadException;
use App\Repository\LeadRepository;
use App\DTO\CreateLeadDTO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class LeadService
{
    /**
     * Constructor with required dependencies
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        private LeadRepository $leadRepository
    ) {}

    /**
     * Creates a new lead from DTO
     *
     * @param CreateLeadDTO $dto The data transfer object containing lead information
     * @return Lead The created lead entity
     * @throws ValidationException When validation fails
     * @throws DuplicateLeadException When lead with same email exists
     */
    public function createLead(CreateLeadDTO $dto): Lead
    {
        $this->logger->info('Creating new lead', ['data' => array_diff_key((array)$dto, ['dateOfBirth' => true])]);

        try {
            $lead = new Lead();
            $this->hydrateLead($lead, $dto);

            // Validate the entity using injected validator
            $violations = $this->validator->validate($lead);
            if (count($violations) > 0) {
                throw new ValidationException($violations);
            }

            // Check for existing lead with same email
            $existingLead = $this->leadRepository->findOneBy(['email' => $lead->getEmail()]);
            if ($existingLead) {
                throw new DuplicateLeadException('A lead with this email already exists');
            }

            // Use cache for rate limiting
            $cacheKey = 'lead_' . md5($lead->getEmail() . $lead->getPhone());
            $item = $this->cache->getItem($cacheKey);
            if ($item->isHit()) {
                throw new DuplicateLeadException('Rate limit exceeded. Please try again later.');
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

    /**
     * Hydrates lead entity with data from DTO
     *
     * @param Lead $lead The lead entity to hydrate
     * @param CreateLeadDTO $dto The data transfer object
     */
    private function hydrateLead(Lead $lead, CreateLeadDTO $dto): void
    {
        $lead->setFirstName($dto->firstName);
        $lead->setLastName($dto->lastName);
        $lead->setEmail($dto->email);
        $lead->setPhone($dto->phone);

        // Date validation is handled by Assert\Date in DTO
        $lead->setDateOfBirth(new \DateTime($dto->dateOfBirth));

        $lead->setAdditionalData($dto->additionalData);
    }
}
