<?php

namespace App\MessageHandler;

use App\Entity\Lead;
use App\Message\LeadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LeadMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(LeadMessage $message): void
    {
        try {
            $leadData = $message->getLeadData();

            $lead = new Lead();
            $lead->setFirstName($leadData['firstName']);
            $lead->setLastName($leadData['lastName']);
            $lead->setEmail($leadData['email']);
            $lead->setPhone($leadData['phone']);
            $lead->setDateOfBirth(new \DateTime($leadData['dateOfBirth']));

            if (isset($leadData['additionalData'])) {
                $lead->setAdditionalData($leadData['additionalData']);
            }

            $this->entityManager->persist($lead);
            $this->entityManager->flush();

            $this->logger->info('Lead processed successfully', [
                'lead_id' => $lead->getId(),
                'email' => $lead->getEmail()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to process lead', [
                'error' => $e->getMessage(),
                'lead_data' => $leadData
            ]);

            throw $e;
        }
    }
} 