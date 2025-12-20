<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use App\Repository\EvenementRepository;
use App\Repository\LigneCommandeRepository;
use App\Repository\TypeBilletRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/owner')]
#[IsGranted('ROLE_OWNER')]
class OwnerController extends AbstractController
{
    #[Route('/dashboard', name: 'app_owner_dashboard')]
    public function dashboard(
        CommandeRepository $commandeRepository,
        EvenementRepository $evenementRepository,
        ClientRepository $clientRepository,
        TypeBilletRepository $typeBilletRepository,
        LigneCommandeRepository $ligneCommandeRepository
    ): Response {
        // ✅ Total billets vendus (toutes lignes de commande)
        $totalBilletsVendus = $ligneCommandeRepository->countTotalTicketsSold();

        // Revenus totaux (méthode existante dans CommandeRepository)
        $revenusTotal = $commandeRepository->getTotalRevenue();

        // Total clients
        $totalClients = count($clientRepository->findAll());

        // Événements actifs
        $evenementsActifs = $evenementRepository->countActiveEvents();

        // Statistiques par événement (déjà existant)
        $ventesByEvent = $ligneCommandeRepository->getVentesByEvent();

        return $this->render('owner/dashboard.html.twig', [
            'total_billets_vendus' => $totalBilletsVendus,
            'revenus_total'        => $revenusTotal,
            'total_clients'        => $totalClients,
            'evenements_actifs'    => $evenementsActifs,
            'ventes_by_event'      => $ventesByEvent,
        ]);
    }

    #[Route('/events', name: 'app_owner_events')]
    public function events(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findAll();

        return $this->render('owner/events.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/clients', name: 'app_owner_clients')]
    public function clients(ClientRepository $clientRepository): Response
    {
        $clients = $clientRepository->findAll();

        return $this->render('owner/clients.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/reports', name: 'app_owner_reports')]
    public function reports(CommandeRepository $commandeRepository): Response
    {
        $startDate = new \DateTime('-30 days');
        $endDate   = new \DateTime();
        $revenueByPeriod = $commandeRepository->getRevenueByPeriod($startDate, $endDate);

        return $this->render('owner/reports.html.twig', [
            'revenue_by_period' => $revenueByPeriod,
        ]);
    }
}
