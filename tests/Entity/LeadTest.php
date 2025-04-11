<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Lead;
use PHPUnit\Framework\TestCase;

class LeadTest extends TestCase
{
    private Lead $lead;

    protected function setUp(): void
    {
        $this->lead = new Lead();
    }

    /**
     * @covers \App\Entity\Lead::setFirstName
     * @covers \App\Entity\Lead::setLastName
     * @covers \App\Entity\Lead::setEmail
     * @covers \App\Entity\Lead::setPhone
     * @covers \App\Entity\Lead::setDateOfBirth
     * @covers \App\Entity\Lead::setAdditionalData
     * @covers \App\Entity\Lead::getFirstName
     * @covers \App\Entity\Lead::getLastName
     * @covers \App\Entity\Lead::getEmail
     * @covers \App\Entity\Lead::getPhone
     * @covers \App\Entity\Lead::getDateOfBirth
     * @covers \App\Entity\Lead::getAdditionalData
     */
    public function testLeadCreation(): void
    {
        $firstName = 'John';
        $lastName = 'Doe';
        $email = 'john@example.com';
        $phone = '+1234567890';
        $dateOfBirth = new \DateTime('1990-01-01');
        $additionalData = ['source' => 'website'];

        $this->lead
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setPhone($phone)
            ->setDateOfBirth($dateOfBirth)
            ->setAdditionalData($additionalData);

        $this->assertEquals($firstName, $this->lead->getFirstName());
        $this->assertEquals($lastName, $this->lead->getLastName());
        $this->assertEquals($email, $this->lead->getEmail());
        $this->assertEquals($phone, $this->lead->getPhone());
        $this->assertEquals($dateOfBirth, $this->lead->getDateOfBirth());
        $this->assertEquals($additionalData, $this->lead->getAdditionalData());
    }

    /**
     * @covers \App\Entity\Lead::setCreatedAt
     * @covers \App\Entity\Lead::setUpdatedAt
     * @covers \App\Entity\Lead::getCreatedAt
     * @covers \App\Entity\Lead::getUpdatedAt
     */
    public function testTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        
        $this->lead->setCreatedAt($now);
        $this->lead->setUpdatedAt($now);

        $this->assertEquals($now, $this->lead->getCreatedAt());
        $this->assertEquals($now, $this->lead->getUpdatedAt());
    }

    /**
     * @covers \App\Entity\Lead::getCreatedByRequest
     * @covers \App\Entity\Lead::getLastModifiedByRequest
     */
    public function testApiRequestRelations(): void
    {
        $this->assertNull($this->lead->getCreatedByRequest());
        $this->assertNull($this->lead->getLastModifiedByRequest());
    }
} 