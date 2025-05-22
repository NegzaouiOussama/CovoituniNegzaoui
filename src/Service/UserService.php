<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserService
{
    private $userRepository;
    private $entityManager;
    private $passwordHasher;
    private $mailer;
    private $urlGenerator;

    public function __construct(
        UtilisateurRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer = null,
        UrlGeneratorInterface $urlGenerator = null
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Register a new user
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $nom
     * @param string $prenom
     * @param string $tel
     * @param string $roleCode
     * @return Utilisateur The created user
     */
    public function registerUser(
        string $username,
        string $email,
        string $password,
        string $nom,
        string $prenom,
        string $tel,
        string $roleCode = 'PASSAGER'
    ): Utilisateur {
        // Check if user already exists
        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new \Exception('User with this email already exists');
        }

        if ($this->userRepository->findOneBy(['username' => $username])) {
            throw new \Exception('Username already taken');
        }

        // Find the Role entity by code
        $role = $this->entityManager->getRepository(\App\Entity\Role::class)->find($roleCode);
        
        // If role doesn't exist, create it
        if (!$role) {
            $role = new \App\Entity\Role();
            $role->setCode($roleCode);
            
            // Set a readable label based on the role code
            $labels = [
                'ADMIN' => 'Administrator',
                'PASSAGER' => 'Passenger',
                'CONDUCTEUR' => 'Driver'
            ];
            
            $role->setLibelle($labels[$roleCode] ?? $roleCode);
            $this->entityManager->persist($role);
            $this->entityManager->flush();
        }

        // Create new user
        $user = new Utilisateur();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setTel($tel);
        $user->setRoleCode($roleCode);
        $user->setRole($role); // Set the Role entity
        $user->setCreatedAt(new \DateTime());

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setMdp($hashedPassword);

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Try to send verification email, but continue if it fails
        $this->sendVerificationEmail($user);

        return $user;
    }

    /**
     * Authenticate a user
     *
     * @param string $email
     * @param string $password
     * @return Utilisateur|null The authenticated user or null if authentication fails
     */
    public function authenticateUser(string $email, string $password): ?Utilisateur
    {
        // Find user by email
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return null;
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return null;
        }

        return $user;
    }

    /**
     * Find a user by ID
     *
     * @param int $id
     * @return Utilisateur|null
     */
    public function findUserById(int $id): ?Utilisateur
    {
        return $this->userRepository->find($id);
    }

    /**
     * Find a user by email
     *
     * @param string $email
     * @return Utilisateur|null
     */
    public function findUserByEmail(string $email): ?Utilisateur
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    /**
     * Find a user by username
     *
     * @param string $username
     * @return Utilisateur|null
     */
    public function findUserByUsername(string $username): ?Utilisateur
    {
        return $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * Update user profile
     *
     * @param int $userId
     * @param array $data
     * @return Utilisateur
     */
    public function updateUserProfile(int $userId, array $data): Utilisateur
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        // Update user data
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        
        if (isset($data['tel'])) {
            $user->setTel($data['tel']);
        }
        
        if (isset($data['username']) && $data['username'] !== $user->getUsername()) {
            // Check if username is already taken
            if ($this->userRepository->findOneBy(['username' => $data['username']])) {
                throw new \Exception('Username already taken');
            }
            $user->setUsername($data['username']);
        }

        if (isset($data['image_path'])) {
            $user->setImagePath($data['image_path']);
        }

        $this->entityManager->flush();

        return $user;
    }

    /**
     * Change user password
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new UserNotFoundException('User not found');
        }

        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return false;
        }

        // Set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setMdp($hashedPassword);

        $this->entityManager->flush();

        return true;
    }

    /**
     * Initiate password reset
     *
     * @param string $email
     * @return bool
     */
    public function initiatePasswordReset(string $email): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Don't reveal that the user doesn't exist
            return false;
        }

        // Generate reset token through a temporary session
        $_SESSION['password_reset_token_' . $email] = bin2hex(random_bytes(16));
        $_SESSION['password_reset_time_' . $email] = time();

        // Send password reset email if mailer exists
        if ($this->mailer && $this->urlGenerator) {
            $this->sendPasswordResetEmail($user);
        }

        return true;
    }

    /**
     * Reset password with token
     *
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        // Find the email associated with this token from session
        $email = null;
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'password_reset_token_') === 0 && $value === $token) {
                $email = substr($key, strlen('password_reset_token_'));
                break;
            }
        }

        if (!$email) {
            return false;
        }

        // Check if token is expired (24 hours)
        $resetTime = $_SESSION['password_reset_time_' . $email] ?? 0;
        if (time() - $resetTime > 86400) {
            return false;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return false;
        }

        // Set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setMdp($hashedPassword);

        // Clear the reset token from session
        unset($_SESSION['password_reset_token_' . $email]);
        unset($_SESSION['password_reset_time_' . $email]);

        $this->entityManager->flush();

        return true;
    }

    /**
     * List all users (with pagination)
     *
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function listUsers(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        return [
            'users' => $this->userRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset),
            'totalUsers' => count($this->userRepository->findAll()),
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Delete a user
     *
     * @param int $userId
     * @return bool
     */
    public function deleteUser(int $userId): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            return false;
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Update user rating
     *
     * @param int $userId
     * @param float $newRating
     * @return bool
     */
    public function updateUserRating(int $userId, float $newRating): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            return false;
        }

        // Calculate new average rating
        $currentRating = (float) $user->getRating();
        $tripsCount = $user->getTripsCount();
        
        // If it's the first rating, just set it directly
        if ($tripsCount === 0) {
            $user->setRating((string) $newRating);
        } else {
            // Calculate weighted average with new rating
            $totalRating = $currentRating * $tripsCount + $newRating;
            $newAverageRating = $totalRating / ($tripsCount + 1);
            $user->setRating((string) $newAverageRating);
        }
        
        // Increment trips count
        $user->setTripsCount($tripsCount + 1);

        $this->entityManager->flush();

        return true;
    }

    /**
     * Send verification email
     *
     * @param Utilisateur $user
     * @return bool Whether the email was sent
     */
    private function sendVerificationEmail(Utilisateur $user): bool
    {
        if (!$this->mailer || !$this->urlGenerator) {
            // If no mailer is configured, we'll just consider the email as verified
            return false;
        }

        try {
            // Use a temporary verification token stored in session
            $verificationToken = bin2hex(random_bytes(16));
            $_SESSION['email_verification_token_' . $user->getEmail()] = $verificationToken;
    
            $verificationUrl = $this->urlGenerator->generate(
                'app_verify_email',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    
            $email = (new Email())
                ->from('noreply@covoituni.com')
                ->to($user->getEmail())
                ->subject('Verify your email address')
                ->html("<p>Hello {$user->getPrenom()},</p>
                       <p>Please confirm your email address by clicking the link below:</p>
                       <p><a href=\"{$verificationUrl}\">Verify Email</a></p>
                       <p>Thank you!</p>");
    
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            // Handle any exceptions that may occur during email sending
            return false;
        }
    }

    /**
     * Send password reset email
     *
     * @param Utilisateur $user
     * @return void
     */
    private function sendPasswordResetEmail(Utilisateur $user): void
    {
        if (!$this->mailer || !$this->urlGenerator) {
            return;
        }

        $resetToken = $_SESSION['password_reset_token_' . $user->getEmail()] ?? '';

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $resetToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('noreply@covoituni.com')
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->html("<p>Hello {$user->getPrenom()},</p>
                   <p>You requested to reset your password. Please click the link below to set a new password:</p>
                   <p><a href=\"{$resetUrl}\">Reset Password</a></p>
                   <p>If you did not request a password reset, please ignore this email.</p>
                   <p>Thank you!</p>");

        $this->mailer->send($email);
    }

    /**
     * Verify user email using verification token
     *
     * @param string $token
     * @return Utilisateur|null
     */
    public function verifyEmailToken(string $token): ?Utilisateur
    {
        // Find the email associated with this token from session
        $email = null;
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'email_verification_token_') === 0 && $value === $token) {
                $email = substr($key, strlen('email_verification_token_'));
                break;
            }
        }

        if (!$email) {
            return null;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return null;
        }

        // Clear the verification token from session
        unset($_SESSION['email_verification_token_' . $email]);

        $this->entityManager->flush();

        return $user;
    }
    
    /**
     * Search users by name, username, or email
     *
     * @param string $searchTerm
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function searchUsers(string $searchTerm, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        // Simple search implementation
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.username LIKE :searchTerm')
            ->orWhere('u.email LIKE :searchTerm')
            ->orWhere('u.nom LIKE :searchTerm')
            ->orWhere('u.prenom LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
            
        // Count total matching users
        $totalUsers = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.username LIKE :searchTerm')
            ->orWhere('u.email LIKE :searchTerm')
            ->orWhere('u.nom LIKE :searchTerm')
            ->orWhere('u.prenom LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'page' => $page,
            'limit' => $limit
        ];
    }
    
    /**
     * Save profile image for a user
     *
     * @param int $userId
     * @param string $filename
     * @return bool
     */
    public function saveProfileImage(int $userId, string $filename): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            return false;
        }

        $user->setImagePath($filename);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Find users by role
     *
     * @param string $roleCode
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function findUsersByRole(string $roleCode, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        
        // Find users with the specified role code
        $users = $this->userRepository->findBy(['roleCode' => $roleCode], ['createdAt' => 'DESC'], $limit, $offset);
        
        // Count total users with this role
        $totalUsers = $this->userRepository->count(['roleCode' => $roleCode]);
        
        return [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Get the mailer service.
     * 
     * @return MailerInterface|null
     */
    public function getMailer(): ?MailerInterface
    {
        return $this->mailer;
    }

    /**
     * Get filtered users for pagination with KnpPaginator
     *
     * @param string $searchTerm
     * @param string $filter
     * @return array
     */
    public function getFilteredUsers(string $searchTerm = '', string $filter = ''): array
    {
        $queryBuilder = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.role', 'r');

        // Apply role filter if provided
        if (!empty($filter)) {
            $roleCode = strtoupper($filter);
            if (in_array($roleCode, ['ADMIN', 'CONDUCTEUR', 'PASSAGER'])) {
                $queryBuilder->andWhere('u.roleCode = :roleCode')
                    ->setParameter('roleCode', $roleCode);
            }
        }

        // Apply search term if provided
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('
                u.nom LIKE :search OR
                u.prenom LIKE :search OR
                u.email LIKE :search OR
                u.username LIKE :search OR
                u.tel LIKE :search
            ')
            ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Count total users for this query
        $countQueryBuilder = clone $queryBuilder;
        $totalUsers = $countQueryBuilder
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Get user collection that will be paginated by KnpPaginator
        $users = $queryBuilder
            ->select('u, r')
            ->orderBy('u.id', 'DESC')
            ->getQuery();

        return [
            'users' => $users,
            'total' => $totalUsers
        ];
    }
} 