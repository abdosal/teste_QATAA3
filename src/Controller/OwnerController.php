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
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;


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
    // KPI principaux
    $totalBilletsVendus = $ligneCommandeRepository->countTotalTicketsSold();
    $revenusTotal       = $commandeRepository->getTotalRevenue();
    $totalClients       = count($clientRepository->findAll());
    $evenementsActifs   = $evenementRepository->countActiveEvents();

    // Statistiques par événement
    $ventesByEvent = $ligneCommandeRepository->getVentesByEvent();

    // Activité récente (5 dernières commandes)
    $lastOrders = $commandeRepository->findLastOrders(5);

    return $this->render('owner/dashboard.html.twig', [
        'total_billets_vendus' => $totalBilletsVendus,
        'revenus_total'        => $revenusTotal,
        'total_clients'        => $totalClients,
        'evenements_actifs'    => $evenementsActifs,
        'ventes_by_event'      => $ventesByEvent,
        'last_orders'          => $lastOrders,
    ]);
}


 #[Route('/events', name: 'app_owner_events')]
public function events(
    EvenementRepository $evenementRepository,
    LigneCommandeRepository $ligneCommandeRepository
): Response {
    $evenements = $evenementRepository->findAll();

    $ticketsByEvent = [];
    foreach ($evenements as $evenement) {
        $ticketsByEvent[$evenement->getId()] =
            $ligneCommandeRepository->countTicketsByEvent($evenement->getId());
    }

    return $this->render('owner/events.html.twig', [
        'evenements'       => $evenements,
        'tickets_by_event' => $ticketsByEvent,
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
public function reports(
    CommandeRepository $commandeRepository,
    LigneCommandeRepository $ligneCommandeRepository,
    ClientRepository $clientRepository,
    EvenementRepository $evenementRepository
): Response {
    $startDate = new \DateTime('-30 days');
    $endDate   = new \DateTime();

    // Commandes sur les 30 derniers jours
    $revenueByPeriod = $commandeRepository->getRevenueByPeriod($startDate, $endDate);

    // KPI
    $totalBilletsVendus = $ligneCommandeRepository->countTotalTicketsSold();
    $revenusTotal       = $commandeRepository->getTotalRevenue();
    $totalClients       = count($clientRepository->findAll());
    $evenementsActifs   = $evenementRepository->countActiveEvents();
    $statsByEvent       = $ligneCommandeRepository->getStatsByEvent();

    // Données pour le graphique
    $labels = [];
    $data   = [];

    foreach ($revenueByPeriod as $commande) {
        $labels[] = $commande->getDateCommande()->format('d/m');
        $data[]   = (float) $commande->getMontantTotal();
    }

    // Résumé période (meilleur jour + moyenne)
    $bestDayLabel   = null;
    $bestDayAmount  = 0;
    $averageRevenue = 0;

    if (!empty($data)) {
        $bestIndex      = array_keys($data, max($data))[0];
        $bestDayLabel   = $labels[$bestIndex];
        $bestDayAmount  = $data[$bestIndex];
        $averageRevenue = array_sum($data) / count($data);
    }

    return $this->render('owner/reports.html.twig', [
        'revenue_by_period'    => $revenueByPeriod,
        'total_billets_vendus' => $totalBilletsVendus,
        'revenus_total'        => $revenusTotal,
        'total_clients'        => $totalClients,
        'evenements_actifs'    => $evenementsActifs,
        'stats_by_event'       => $statsByEvent,
        'labels'               => $labels,
        'data'                 => $data,
        'best_day_label'       => $bestDayLabel,
        'best_day_amount'      => $bestDayAmount,
        'average_revenue'      => $averageRevenue,
    ]);
}
}