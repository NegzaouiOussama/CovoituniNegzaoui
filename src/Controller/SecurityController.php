<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        // Handle form submission
        if ($request->isMethod('POST')) {
            try {
                // Get form data
                $username = $request->request->get('username');
                $email = $request->request->get('email');
                $password = $request->request->get('password');
                $passwordConfirm = $request->request->get('password_confirm');
                $nom = $request->request->get('nom');
                $prenom = $request->request->get('prenom');
                $tel = $request->request->get('tel');
                $roleCode = $request->request->get('role_code', 'PASSAGER'); // Get role code with default PASSAGER

                // Validate form data
                if (empty($username) || empty($email) || empty($password) || empty($nom) || empty($prenom) || empty($tel)) {
                    $this->addFlash('error', 'All fields are required');
                    return $this->redirectToRoute('app_register');
                }

                if ($password !== $passwordConfirm) {
                    $this->addFlash('error', 'Passwords do not match');
                    return $this->redirectToRoute('app_register');
                }

                // Validate role code
                if (!in_array($roleCode, ['PASSAGER', 'CONDUCTEUR'])) {
                    $roleCode = 'PASSAGER'; // Default to PASSAGER if invalid
                }

                // Register the user
                $user = $this->userService->registerUser(
                    $username,
                    $email,
                    $password,
                    $nom,
                    $prenom,
                    $tel,
                    $roleCode
                );

                // Check if we have a mailer service configured
                if (property_exists($this->userService, 'mailer') && $this->userService->getMailer()) {
                    $this->addFlash('success', 'Account created successfully! Please check your email to verify your account.');
                } else {
                    // If no mailer, just create the account without verification
                    $this->addFlash('success', 'Account created successfully! You can now log in.');
                }
                
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on the firewall
        throw new \Exception('This method should never be called directly');
    }

    #[Route('/login-redirect', name: 'app_login_redirect')]
    public function loginRedirect(): Response
    {
        $user = $this->getUser();
        
        // Redirect based on user role
        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('app_admin_dashboard');
            } elseif (in_array('ROLE_CONDUCTEUR', $user->getRoles())) {
                return $this->redirectToRoute('app_conducteur_dashboard');
            } elseif (in_array('ROLE_PASSAGER', $user->getRoles())) {
                return $this->redirectToRoute('app_passager_dashboard');
            }
        }
        
        // Fallback to home page if no specific role matches
        return $this->redirectToRoute('app_home');
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token): Response
    {
        $user = $this->userService->verifyEmailToken($token);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification link');
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Email verified successfully! You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            if (!empty($email)) {
                // Always return success even if email doesn't exist (security best practice)
                $this->userService->initiatePasswordReset($email);
                $this->addFlash('success', 'If your email exists in our system, you will receive a password reset link shortly.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token): Response
    {
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            
            if (empty($password) || $password !== $passwordConfirm) {
                $this->addFlash('error', 'Passwords do not match or are empty');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }
            
            $success = $this->userService->resetPassword($token, $password);
            
            if ($success) {
                $this->addFlash('success', 'Password changed successfully. You can now log in with your new password.');
                return $this->redirectToRoute('app_login');
            }
            
            $this->addFlash('error', 'Invalid or expired reset token');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token
        ]);
    }
} 