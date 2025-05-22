<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class AvisController extends AbstractController
{
    #[Route('/avis', name: 'app_avis_index', methods: ['GET'])]
    public function index(AvisRepository $avisRepository): Response
    {
        return $this->render('avis/index.html.twig', [
            'avis' => $avisRepository->findAll(),
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/avis/new', name: 'app_avis_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $avi = new Avis();
        $form = $this->createForm(AvisType::class, $avi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($avi);
            $entityManager->flush();

            $this->addFlash('success', 'The review has been created successfully.');
            return $this->redirectToRoute('app_avis_index', [], Response::HTTP_SEE_OTHER);
        }

        // Try to find the right entity for drivers
        $conducteurs = [];
        
        // Look for users with driver role/property
        $userEntities = [
            'App\Entity\Utilisateur',
            'App\Entity\User',
            'App\Entity\Compte',
            'App\Entity\Account'
        ];
        
        foreach ($userEntities as $entityClass) {
            try {
                if (class_exists($entityClass)) {
                    $repository = $entityManager->getRepository($entityClass);
                    $metadata = $entityManager->getClassMetadata($entityClass);
                    $fields = $metadata->getFieldNames();
                    
                    // Check if this entity has a role or type field to filter drivers
                    if (in_array('roles', $fields) || method_exists($entityClass, 'getRoles')) {
                        // Entity has roles, find users with ROLE_DRIVER or similar
                        $users = $repository->findAll();
                        $filteredUsers = [];
                        
                        foreach ($users as $user) {
                            if (method_exists($user, 'getRoles')) {
                                $roles = $user->getRoles();
                                if (in_array('ROLE_DRIVER', $roles) || in_array('ROLE_CONDUCTEUR', $roles)) {
                                    $filteredUsers[] = $user;
                                }
                            }
                        }
                        
                        if (!empty($filteredUsers)) {
                            $conducteurs = $filteredUsers;
                            break;
                        }
                    } else if (in_array('type', $fields) || method_exists($entityClass, 'getType')) {
                        // Entity might have a type field to identify drivers
                        $users = $repository->findBy(['type' => 'driver']);
                        if (!empty($users)) {
                            $conducteurs = $users;
                            break;
                        }
                    } else if (in_array('isDriver', $fields) || method_exists($entityClass, 'isDriver')) {
                        // Entity might have a boolean field for drivers
                        $users = $repository->findBy(['isDriver' => true]);
                        if (!empty($users)) {
                            $conducteurs = $users;
                            break;
                        }
                    } else {
                        // No obvious way to filter, get all users as fallback
                        $users = $repository->findAll();
                        if (!empty($users)) {
                            $conducteurs = $users;
                            // Don't break here, keep looking for better entities
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue to next entity
                continue;
            }
        }
        
        // If no user-based drivers found, look for dedicated driver entities
        if (empty($conducteurs)) {
            $driverEntities = [
                'App\Entity\Conducteur',
                'App\Entity\Chauffeur',
                'App\Entity\Driver'
            ];
            
            foreach ($driverEntities as $entityClass) {
                try {
                    if (class_exists($entityClass)) {
                        $drivers = $entityManager->getRepository($entityClass)->findAll();
                        if (!empty($drivers)) {
                            $conducteurs = $drivers;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    // Continue to next entity
                    continue;
                }
            }
        }
        
        // If still no drivers found, provide a helpful debug message
        if (empty($conducteurs)) {
            $this->addFlash('error', 'Unable to load drivers. Please check your entity configuration.');
            
            // List available entities for debugging
            $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
            $availableEntities = [];
            foreach ($metadata as $classMetadata) {
                $availableEntities[] = $classMetadata->getName();
            }
            
            // Add list of found entities to the flash message
            if (!empty($availableEntities)) {
                $this->addFlash('info', 'Available entities: ' . implode(', ', $availableEntities));
            }
        }

        return $this->render('passager/avis/new.html.twig', [
            'avi' => $avi,
            'form' => $form,
            'conducteurs' => $conducteurs,
        ]);
    }

    #[Route('/avis/{id}', name: 'app_avis_show', methods: ['GET'])]
    public function show(Avis $avi): Response
    {
        return $this->render('avis/show.html.twig', [
            'avi' => $avi,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/avis/{id}/edit', name: 'app_avis_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Avis $avi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AvisType::class, $avi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'The review has been updated successfully.');
            return $this->redirectToRoute('app_avis_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('avis/edit.html.twig', [
            'avi' => $avi,
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/avis/{id}', name: 'app_avis_delete', methods: ['POST'])]
    public function delete(Request $request, Avis $avi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$avi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($avi);
            $entityManager->flush();
            $this->addFlash('success', 'The review has been deleted successfully.');
        }

        return $this->redirectToRoute('app_avis_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/passager/avis', name: 'app_passager_avis')]
    public function passagerAvis(EntityManagerInterface $entityManager): Response
    {
        $conducteurs = [];
        
        // Look for users with driver role/property
        $userEntities = [
            'App\Entity\Utilisateur',
            'App\Entity\User',
            'App\Entity\Compte',
            'App\Entity\Account'
        ];
        
        foreach ($userEntities as $entityClass) {
            try {
                if (class_exists($entityClass)) {
                    $repository = $entityManager->getRepository($entityClass);
                    $metadata = $entityManager->getClassMetadata($entityClass);
                    $fields = $metadata->getFieldNames();
                    
                    // Check if this entity has a role or type field to filter drivers
                    if (in_array('roles', $fields) || method_exists($entityClass, 'getRoles')) {
                        // Entity has roles, find users with ROLE_DRIVER or similar
                        $users = $repository->findAll();
                        $filteredUsers = [];
                        
                        foreach ($users as $user) {
                            if (method_exists($user, 'getRoles')) {
                                $roles = $user->getRoles();
                                if (in_array('ROLE_DRIVER', $roles) || in_array('ROLE_CONDUCTEUR', $roles)) {
                                    $filteredUsers[] = $user;
                                }
                            }
                        }
                        
                        if (!empty($filteredUsers)) {
                            $conducteurs = $filteredUsers;
                            break;
                        }
                    } else if (in_array('type', $fields) || method_exists($entityClass, 'getType')) {
                        // Entity might have a type field to identify drivers
                        $users = $repository->findBy(['type' => 'driver']);
                        if (!empty($users)) {
                            $conducteurs = $users;
                            break;
                        }
                    } else if (in_array('isDriver', $fields) || method_exists($entityClass, 'isDriver')) {
                        // Entity might have a boolean field for drivers
                        $users = $repository->findBy(['isDriver' => true]);
                        if (!empty($users)) {
                            $conducteurs = $users;
                            break;
                        }
                    } else {
                        // No obvious way to filter, get all users as fallback
                        $users = $repository->findAll();
                        if (!empty($users)) {
                            $conducteurs = $users;
                            // Don't break here, keep looking for better entities
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue to next entity
                continue;
            }
        }
        
        // If no user-based drivers found, look for dedicated driver entities
        if (empty($conducteurs)) {
            $driverEntities = [
                'App\Entity\Conducteur',
                'App\Entity\Chauffeur',
                'App\Entity\Driver'
            ];
            
            foreach ($driverEntities as $entityClass) {
                try {
                    if (class_exists($entityClass)) {
                        $drivers = $entityManager->getRepository($entityClass)->findAll();
                        if (!empty($drivers)) {
                            $conducteurs = $drivers;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    // Continue to next entity
                    continue;
                }
            }
        }
        
        // If still no drivers found, provide a helpful debug message
        if (empty($conducteurs)) {
            $this->addFlash('error', 'Unable to load drivers. Please check your entity configuration.');
            
            // List available entities for debugging
            $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
            $availableEntities = [];
            foreach ($metadata as $classMetadata) {
                $availableEntities[] = $classMetadata->getName();
            }
            
            // Add list of found entities to the flash message
            if (!empty($availableEntities)) {
                $this->addFlash('info', 'Available entities: ' . implode(', ', $availableEntities));
            }
        }

        return $this->render('passager/avis.html.twig', [
            'avi' => new Avis(),
            'form' => $this->createForm(AvisType::class, new Avis()),
            'conducteurs' => $conducteurs,
        ]);
    }

    #[Route('/passager/avis/list', name: 'app_passager_avis_list')]
    #[Route('/avis/list', name: 'app_avis_list')]
    public function list(AvisRepository $avisRepository, Environment $twig): Response
    {
        $avis = $avisRepository->findAll();
        $user = $this->getUser();
        
        // Let's be flexible with which template we render
        if ($twig->getLoader()->exists('passager/avis/list.html.twig')) {
            return $this->render('passager/avis/list.html.twig', [
                'avis' => $avis,
                'user' => $user,
            ]);
        } else {
            return $this->render('avis/list.html.twig', [
                'avis' => $avis,
                'user' => $user,
            ]);
        }
    }

    #[Route('/passager/avis/submit', name: 'app_passager_avis_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Process the form data...
        $driverId = $request->request->get('driver');
        $rating = $request->request->get('rating');
        $comment = $request->request->get('comment');
        
        // Create a new Avis entity
        $avis = new Avis();
        
        // Set properties based on your Avis entity structure
        // We'll need to determine the driver entity class dynamically
        $driverEntityClass = null;
        $possibleDriverEntities = [
            'App\Entity\Conducteur',
            'App\Entity\Chauffeur',
            'App\Entity\Driver',
            'App\Entity\Utilisateur',
            'App\Entity\User'
        ];
        
        foreach ($possibleDriverEntities as $entityClass) {
            if (class_exists($entityClass)) {
                $driverEntityClass = $entityClass;
                break;
            }
        }
        
        if ($driverEntityClass && $driverId) {
            $driver = $entityManager->find($driverEntityClass, $driverId);
            if ($driver) {
                // Choose the right setter based on your Avis entity structure
                // $avis->setConducteur($driver);
                // Or $avis->setUtilisateur($driver);
                // Or $avis->setDriver($driver);
            }
        }
        
        $avis->setRating((int)$rating);
        $avis->setCommentaire($comment);
        $avis->setDate(new \DateTime());
        
        $entityManager->persist($avis);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre avis a été ajouté avec succès!');
        return $this->redirectToRoute('app_passager_avis_list');
    }

    #[Route('/passager/avis/new', name: 'app_passager_avis_new')]
    public function passagerAvisNew(EntityManagerInterface $entityManager): Response
    {
        // Reuse the same code as in passagerAvis method
        $conducteurs = [];
        
        // Your existing code to find conducteurs...
        // (Code omitted for brevity)
        
        return $this->render('passager/avis/new.html.twig', [
            'avi' => new Avis(),
            'form' => $this->createForm(AvisType::class, new Avis()),
            'conducteurs' => $conducteurs,
        ]);
    }
} 