<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_admin_category_index', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categorieRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();
            
            $this->addFlash('success', 'La catégorie a été créée avec succès !');
            return $this->redirectToRoute('app_admin_category_index');
        }
        
        return $this->render('admin/category/new.html.twig', [
            'category' => $categorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'La catégorie a été modifiée avec succès !');
            return $this->redirectToRoute('app_admin_category_index');
        }
        
        return $this->render('admin/category/edit.html.twig', [
            'category' => $categorie,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Check if CSRF token is valid
        if (!$this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_category_index');
        }
        
        // Check if the category is used by any cars
        if (count($categorie->getCars()) > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle est utilisée par des voitures.');
            return $this->redirectToRoute('app_admin_category_index');
        }
        
        try {
            $entityManager->remove($categorie);
            $entityManager->flush();
            $this->addFlash('success', 'La catégorie a été supprimée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur s\'est produite lors de la suppression : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_admin_category_index');
    }

    #[Route('/{id}', name: 'app_admin_category_show', methods: ['GET'])]
    public function show(Categorie $categorie): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/category/show.html.twig', [
            'category' => $categorie,
        ]);
    }
} 