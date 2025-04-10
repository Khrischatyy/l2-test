<?php

namespace App\Message;

class LeadMessage
{
    private array $leadData;

    public function __construct(array $leadData)
    {
        $this->leadData = $leadData;
    }

    public function getLeadData(): array
    {
        return $this->leadData;
    }
} 