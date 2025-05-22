<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Creates a new admin user',
)]
class CreateAdminUserCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private UtilisateurRepository $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UtilisateurRepository $userRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Check if admin already exists
        $existingUser = $this->userRepository->findOneBy(['email' => 'admin@covoituni.tn']);
        
        if ($existingUser) {
            $io->error('Admin user with email admin@covoituni.tn already exists!');
            return Command::FAILURE;
        }
        
        // Get the ADMIN role
        $roleAdmin = $this->entityManager->getRepository(Role::class)->find('ADMIN');
        
        if (!$roleAdmin) {
            $io->error('The ADMIN role does not exist in the database!');
            return Command::FAILURE;
        }
        
        try {
            // Create a new admin user
            $admin = new Utilisateur();
            $admin->setUsername('admin');
            $admin->setNom('Admin');
            $admin->setPrenom('Super');
            $admin->setTel('12345678');
            $admin->setEmail('admin@covoituni.tn');
            
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
            $admin->setMdp($hashedPassword);
            
            // Set the role
            $admin->setRole($roleAdmin);
            $admin->setRoleCode('ADMIN');
            
            // Set other required fields
            $admin->setCreatedAt(new \DateTime());
            
            // Save to database using the repository
            $this->userRepository->save($admin, true);
            
            $io->success('Admin user created successfully!');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 