<?php

namespace App\Controller;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoleController extends AbstractController
{
    #[Route('/admin/setup-roles', name: 'app_setup_roles')]
    public function setupRoles(EntityManagerInterface $entityManager): Response
    {
        $roles = [
            ['code' => 'PASSAGER', 'libelle' => 'Passager'],
            ['code' => 'CONDUCTEUR', 'libelle' => 'Conducteur'],
            ['code' => 'ADMIN', 'libelle' => 'Administrateur']
        ];

        $addedRoles = [];
        $existingRoles = [];

        foreach ($roles as $roleData) {
            // Check if role already exists
            $existingRole = $entityManager->getRepository(Role::class)->find($roleData['code']);
            
            if ($existingRole === null) {
                // Create new role
                $role = new Role();
                $role->setCode($roleData['code']);
                $role->setLibelle($roleData['libelle']);
                $entityManager->persist($role);
                $addedRoles[] = $roleData['code'];
            } else {
                $existingRoles[] = $roleData['code'];
            }
        }

        // Save to database
        $entityManager->flush();

        return $this->render('role/setup.html.twig', [
            'added_roles' => $addedRoles,
            'existing_roles' => $existingRoles,
        ]);
    }
} 