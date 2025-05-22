<?php

namespace App\Controller;

use App\Service\UserService;
use App\Service\UserBanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    private $userService;
    private $userBanService;

    public function __construct(UserService $userService, UserBanService $userBanService)
    {
        $this->userService = $userService;
        $this->userBanService = $userBanService;
    }

    #[Route('/profile', name: 'app_user_profile')]
    public function profile(): Response
    {
        // Get the current user
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_user_edit_profile')]
    public function editProfile(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            try {
                // Récupérer les données du formulaire
                $nom = $request->request->get('nom');
                $prenom = $request->request->get('prenom');
                $username = $request->request->get('username');
                $tel = $request->request->get('tel');
                $email = $request->request->get('email', $user->getEmail());  // Garder l'email existant si non fourni
                
                // Vérifier que les champs requis ne sont pas vides
                if (empty($nom) || empty($prenom) || empty($username) || empty($tel) || empty($email)) {
                    $this->addFlash('error', 'Tous les champs sont obligatoires');
                    return $this->render('user/edit_profile.html.twig', [
                        'user' => $user,
                    ]);
                }
                
                // Vérifier l'unicité du nom d'utilisateur
                if ($username !== $user->getUsername()) {
                    $existingUser = $entityManager->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['username' => $username]);
                    if ($existingUser && $existingUser->getId() !== $user->getId()) {
                        $this->addFlash('error', 'Ce nom d\'utilisateur est déjà utilisé');
                        return $this->render('user/edit_profile.html.twig', [
                            'user' => $user,
                        ]);
                    }
                }
                
                // Vérifier l'unicité de l'email
                if ($email !== $user->getEmail()) {
                    $existingUser = $entityManager->getRepository(\App\Entity\Utilisateur::class)->findOneBy(['email' => $email]);
                    if ($existingUser && $existingUser->getId() !== $user->getId()) {
                        $this->addFlash('error', 'Cet email est déjà utilisé');
                        return $this->render('user/edit_profile.html.twig', [
                            'user' => $user,
                        ]);
                    }
                    $user->setEmail($email);
                }
                
                // Mettre à jour l'utilisateur
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setUsername($username);
                $user->setTel($tel);
                
                // Valider l'entité avec le validateur
                $errors = $validator->validate($user);
                
                if (count($errors) > 0) {
                    // S'il y a des erreurs de validation, afficher le premier message d'erreur
                    $this->addFlash('error', $errors[0]->getMessage());
                    return $this->render('user/edit_profile.html.twig', [
                        'user' => $user,
                    ]);
                }
                
                $data = [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'username' => $username,
                    'tel' => $tel,
                    'email' => $email,
                ];

                // Handle profile image upload
                $profileImage = $request->files->get('profileImage');
                if ($profileImage) {
                    // Valider le type de fichier
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $mimeType = $profileImage->getMimeType();
                    
                    if (!in_array($mimeType, $allowedMimeTypes)) {
                        $this->addFlash('error', 'Le format de l\'image n\'est pas valide. Formats acceptés: JPG, PNG, GIF, WEBP');
                        return $this->render('user/edit_profile.html.twig', [
                            'user' => $user,
                        ]);
                    }
                    
                    // Valider la taille du fichier (max 2MB)
                    if ($profileImage->getSize() > 2 * 1024 * 1024) {
                        $this->addFlash('error', 'L\'image ne doit pas dépasser 2MB');
                        return $this->render('user/edit_profile.html.twig', [
                            'user' => $user,
                        ]);
                    }
                    
                    // Define upload directory
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/profile_images';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($uploadsDirectory)) {
                        mkdir($uploadsDirectory, 0777, true);
                    }
                    
                    // Generate a unique filename
                    $originalExtension = $profileImage->getClientOriginalExtension();
                    $newFilename = uniqid() . '.' . $originalExtension;
                    
                    // Move the file to the uploads directory
                    $profileImage->move($uploadsDirectory, $newFilename);
                    
                    // Update the user's image path
                    $data['image_path'] = 'uploads/profile_images/' . $newFilename;
                }

                $this->userService->updateUserProfile($user->getId(), $data);
                
                $this->addFlash('success', 'Profile updated successfully');
                return $this->redirectToRoute('app_user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('user/edit_profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/change-password', name: 'app_user_change_password')]
    public function changePassword(Request $request, ValidatorInterface $validator): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Vérification des champs vides
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires');
                return $this->render('user/change_password.html.twig');
            }
            
            // Vérification de la longueur du mot de passe
            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères');
                return $this->render('user/change_password.html.twig');
            }
            
            // Vérification de la complexité du mot de passe
            $hasLetter = preg_match('/[a-zA-Z]/', $newPassword);
            $hasNumber = preg_match('/\d/', $newPassword);
            $hasSpecialChar = preg_match('/[^a-zA-Z\d]/', $newPassword);
            
            if (!$hasLetter || !$hasNumber || !$hasSpecialChar) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une lettre, un chiffre et un caractère spécial');
                return $this->render('user/change_password.html.twig');
            }
            
            // Vérification que le nouveau mot de passe est différent de l'ancien
            if ($currentPassword === $newPassword) {
                $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien');
                return $this->render('user/change_password.html.twig');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
                return $this->render('user/change_password.html.twig');
            } else {
                try {
                    $success = $this->userService->changePassword(
                        $user->getId(),
                        $currentPassword,
                        $newPassword
                    );

                    if ($success) {
                        $this->addFlash('success', 'Mot de passe modifié avec succès');
                        return $this->redirectToRoute('app_user_profile');
                    } else {
                        $this->addFlash('error', 'Le mot de passe actuel est incorrect');
                        return $this->render('user/change_password.html.twig');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    return $this->render('user/change_password.html.twig');
                }
            }
        }

        return $this->render('user/change_password.html.twig');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function listUsers(Request $request, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        // Check if the user has admin rights
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 2); // Reduced to 2 records per page for testing
        $searchTerm = $request->query->get('search', '');
        $filter = $request->query->get('filter', '');

        try {
            $usersData = $this->userService->getFilteredUsers($searchTerm, $filter);
            
            // Use KnpPaginator to paginate the results
            $pagination = $paginator->paginate(
                $usersData['users'], // Data to paginate
                $page,               // Current page
                $limit               // Items per page
            );
            
            // Check ban status for each user
            $bannedStatuses = [];
            foreach ($pagination as $user) {
                $bannedStatuses[$user->getId()] = $this->userBanService->isUserBanned($user);
            }

            return $this->render('user/list.html.twig', [
                'pagination' => $pagination,
                'total_users' => $usersData['total'],
                'search' => $searchTerm,
                'filter' => $filter,
                'sort' => $request->query->get('sort', 'id'),
                'direction' => $request->query->get('direction', 'asc'),
                'bannedStatuses' => $bannedStatuses,
                'user' => $this->getUser() // Include the current user for the template
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/admin/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, Request $request): Response
    {
        // Check if the user has admin rights
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // CSRF token validation
        if (!$this->isCsrfTokenValid('delete-user-'.$id, $request->request->get('_token'))) {
            throw new AccessDeniedException('Invalid CSRF token');
        }

        try {
            $success = $this->userService->deleteUser($id);
            
            if ($success) {
                $this->addFlash('success', 'User deleted successfully');
            } else {
                $this->addFlash('error', 'User not found');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/users/{id}/edit-roles', name: 'app_user_edit_roles', methods: ['GET', 'POST'])]
    public function editRoles(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Check if the user has admin rights
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->userService->findUserById($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('app_admin_users');
        }
        
        if ($request->isMethod('POST')) {
            // CSRF token validation
            if (!$this->isCsrfTokenValid('edit-roles'.$id, $request->request->get('_token'))) {
                throw new AccessDeniedException('Invalid CSRF token');
            }
            
            $roleCode = $request->request->get('role_code', 'PASSAGER');
            
            // Validate role code
            if (!in_array($roleCode, ['PASSAGER', 'CONDUCTEUR', 'ADMIN'])) {
                $this->addFlash('error', 'Invalid role selected');
                return $this->redirectToRoute('app_user_edit_roles', ['id' => $id]);
            }
            
            // Find the Role entity
            $role = $entityManager->getRepository(\App\Entity\Role::class)->find($roleCode);
            if (!$role) {
                $this->addFlash('error', 'Role not found in database');
                return $this->redirectToRoute('app_user_edit_roles', ['id' => $id]);
            }
            
            // Update the user's role
            $user->setRoleCode($roleCode);
            $user->setRole($role);
            $entityManager->flush();
            
            $this->addFlash('success', 'User role updated successfully');
            return $this->redirectToRoute('app_admin_users');
        }
        
        return $this->render('user/edit_roles.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(int $id, Request $request): Response
    {
        // Check if the user has admin rights
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // CSRF token validation
        if (!$this->isCsrfTokenValid('ban-user-'.$id, $request->request->get('_token'))) {
            throw new AccessDeniedException('Invalid CSRF token');
        }

        $user = $this->userService->findUserById($id);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_admin_users');
        }
        
        // Don't allow banning administrators if not super admin
        if ($user->getRoleCode() === 'ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas bannir un administrateur');
            return $this->redirectToRoute('app_admin_users');
        }
        
        // Toggle ban status using the service
        $isBanned = $this->userBanService->toggleBanStatus($user);
        
        if (!$isBanned) {
            $this->addFlash('success', 'L\'utilisateur a été débloqué avec succès');
        } else {
            $this->addFlash('success', 'L\'utilisateur a été banni avec succès');
        }
        
        return $this->redirectToRoute('app_admin_users', [
            'filter' => $request->query->get('filter'),
            'search' => $request->query->get('search'),
            'page' => $request->query->get('page', 1)
        ]);
    }
} 