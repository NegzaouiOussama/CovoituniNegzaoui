<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\RequestStack;

class UserBanService
{
    private $requestStack;
    
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    
    /**
     * Ban a user
     */
    public function banUser(Utilisateur $user): void
    {
        $session = $this->requestStack->getSession();
        $bannedUsers = $session->get('banned_users', []);
        
        if (!in_array($user->getId(), $bannedUsers)) {
            $bannedUsers[] = $user->getId();
            $session->set('banned_users', $bannedUsers);
        }
    }
    
    /**
     * Unban a user
     */
    public function unbanUser(Utilisateur $user): void
    {
        $session = $this->requestStack->getSession();
        $bannedUsers = $session->get('banned_users', []);
        
        $bannedUsers = array_filter($bannedUsers, function($id) use ($user) {
            return $id !== $user->getId();
        });
        
        $session->set('banned_users', $bannedUsers);
    }
    
    /**
     * Check if a user is banned
     */
    public function isUserBanned(Utilisateur $user): bool
    {
        $session = $this->requestStack->getSession();
        $bannedUsers = $session->get('banned_users', []);
        
        return in_array($user->getId(), $bannedUsers);
    }
    
    /**
     * Toggle ban status for a user
     */
    public function toggleBanStatus(Utilisateur $user): bool
    {
        if ($this->isUserBanned($user)) {
            $this->unbanUser($user);
            return false; // user is now unbanned
        } else {
            $this->banUser($user);
            return true; // user is now banned
        }
    }
} 