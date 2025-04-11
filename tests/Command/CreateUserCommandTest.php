<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\CreateUserCommand;
use App\Tests\Constants\TestConstants;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $command = new CreateUserCommand($this->entityManager, $this->passwordHasher);
        
        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @covers \App\Command\CreateUserCommand::execute
     * @covers \App\Command\CreateUserCommand::__construct
     */
    public function testExecuteCreatesUser(): void
    {
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getEmail() === TestConstants::TEST_USER_EMAIL
                    && $user->getPassword() === 'hashed_password'
                    && $user->getRoles() === ['ROLE_USER'];
            }));

        $this->commandTester->execute([
            'email' => TestConstants::TEST_USER_EMAIL,
            'password' => TestConstants::TEST_USER_PASSWORD
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('User successfully created!', $this->commandTester->getDisplay());
    }

    /**
     * @covers \App\Command\CreateUserCommand::execute
     * @covers \App\Command\CreateUserCommand::__construct
     */
    public function testExecuteWithCustomCredentials(): void
    {
        $email = 'custom@example.com';
        $password = 'custom_password';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) use ($email) {
                return $user->getEmail() === $email;
            }));

        $this->commandTester->execute([
            'email' => $email,
            'password' => $password
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    /**
     * @covers \App\Command\CreateUserCommand::execute
     * @covers \App\Command\CreateUserCommand::__construct
     */
    public function testExecuteHandlesDuplicateUser(): void
    {
        $this->entityManager
            ->method('persist')
            ->willThrowException(new \Exception('Duplicate entry'));

        $this->commandTester->execute([
            'email' => TestConstants::TEST_USER_EMAIL,
            'password' => TestConstants::TEST_USER_PASSWORD
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('User already exists', $this->commandTester->getDisplay());
    }
} 