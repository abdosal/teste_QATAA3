<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\TypeBillet;
use App\Form\EvenementType;
use App\Form\TypeBilletType;
use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use App\Repository\EvenementRepository;
use App\Repository\LigneCommandeRepository;
use App\Repository\TypeBilletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/responsable')]
#[IsGranted('ROLE_RESPONSABLE')]
class ResponsableController extends AbstractController
{
    #[Route('/dashboard', name: 'app_responsable_dashboard')]
    public function dashboard(
        CommandeRepository $commandeRepository,
        EvenementRepository $evenementRepository,
        ClientRepository $clientRepository,
        TypeBilletRepository $typeBilletRepository,
        LigneCommandeRepository $ligneCommandeRepository
    ): Response {
        // KPIs
        // ✅ Correction : on utilise LigneCommandeRepository pour compter le volume de billets
        $billetsVendusAujourdhui = $ligneCommandeRepository->countTicketsSoldToday();

        $evenementsActifs = $evenementRepository->countActiveEvents();
        $utilisateursInscrits = count($clientRepository->findActiveClients());
        $billetsDisponibles = $typeBilletRepository->getTotalRemainingTickets();
        
        // Revenus
        $revenusTotal = $commandeRepository->getTotalRevenue();

        return $this->render('responsable/dashboard.html.twig', [
            'billets_vendus' => $billetsVendusAujourdhui,
            'evenements_actifs' => $evenementsActifs,
            'utilisateurs_inscrits' => $utilisateursInscrits,
            'billets_disponibles' => $billetsDisponibles,
            'revenus_total' => $revenusTotal,
        ]);
    }

    // ========== GESTION ÉVÉNEMENTS ==========
    
    #[Route('/evenements', name: 'app_responsable_evenements')]
    public function evenements(EvenementRepository $evenementRepository): Response
{
    // 1) Mettre à jour les statuts en fonction de la date
    $evenementRepository->updateStatusesByDate();

    // 2) Afficher tous les événements (actifs + inactifs) avec le bon statut
    $evenements = $evenementRepository->findAllOrderByDate();

    return $this->render('responsable/evenements/index.html.twig', [
        'evenements' => $evenements,
    ]);
}




    #[Route('/evenement/new', name: 'app_responsable_evenement_new')]
    public function newEvenement(Request $request, EntityManagerInterface $em): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload d'image
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/images/events';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                try {
                    $imageFile->move($uploadDir, $newFilename);
                    $evenement->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }
            
            $em->persist($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('app_responsable_evenements');
        }

        return $this->render('responsable/evenements/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/evenement/{id}/edit', name: 'app_responsable_evenement_edit')]
    public function editEvenement(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                if ($evenement->getImage()) {
                    $oldImagePath = $this->getParameter('kernel.project_dir').'/public/images/events/'.$evenement->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                
                $uploadDir = $this->getParameter('kernel.project_dir').'/public/images/events';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                try {
                    $imageFile->move($uploadDir, $newFilename);
                    $evenement->setImage($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }
            
            $em->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('app_responsable_evenements');
        }

        return $this->render('responsable/evenements/edit.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
        ]);
    }

    #[Route('/evenement/{id}/delete', name: 'app_responsable_evenement_delete', methods: ['POST'])]
    public function deleteEvenement(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $em->remove($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('app_responsable_evenements');
    }

    #[Route('/evenement/{id}', name: 'app_responsable_evenement_detail')]
    public function detailEvenement(Evenement $evenement): Response
    {
        return $this->render('responsable/evenements/detail.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    // ========== GESTION BILLETS ==========
    
    #[Route('/evenement/{id}/billets', name: 'app_responsable_billets')]
    public function billets(Evenement $evenement): Response
    {
        return $this->render('responsable/billets/index.html.twig', [
            'evenement' => $evenement,
            'billets' => $evenement->getTypeBillets(),
        ]);
    }

    #[Route('/evenement/{id}/billet/new', name: 'app_responsable_billet_new')]
    public function newBillet(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em
    ): Response {
        $typeBillet = new TypeBillet();
        $typeBillet->setEvenement($evenement);
        
        $form = $this->createForm(TypeBilletType::class, $typeBillet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $typeBillet->setQuantiteRestante($typeBillet->getQuantiteTotale());
            
            $em->persist($typeBillet);
            $em->flush();

            $this->addFlash('success', 'Type de billet créé avec succès !');
            return $this->redirectToRoute('app_responsable_billets', ['id' => $evenement->getId()]);
        }

        return $this->render('responsable/billets/new.html.twig', [
            'form' => $form,
            'evenement' => $evenement,
        ]);
    }

    // ========== GESTION CLIENTS ==========
    
    #[Route('/clients', name: 'app_responsable_clients')]
    public function clients(ClientRepository $clientRepository): Response
    {
        $clients = $clientRepository->findAll();

        return $this->render('responsable/clients/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/client/{id}/suspend', name: 'app_responsable_client_suspend', methods: ['POST'])]
    public function suspendClient(
        Request $request,
        int $id,
        ClientRepository $clientRepository,
        EntityManagerInterface $em
    ): Response {
        $client = $clientRepository->find($id);

        if ($client && $this->isCsrfTokenValid('suspend'.$id, $request->request->get('_token'))) {
            $client->setStatutCompte('suspendu');
            $em->flush();

            $this->addFlash('success', 'Client suspendu avec succès !');
        }

        return $this->redirectToRoute('app_responsable_clients');
    }
}
