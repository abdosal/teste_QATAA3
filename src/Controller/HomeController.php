<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EvenementRepository $evenementRepository,
        TicketRepository $ticketRepository,
        EntityManagerInterface $em
    ): Response {
        // Événements actifs pour le slider
        $evenements = $evenementRepository->findActiveEvents();

        // 1) Nombre total d’événements
        $totalEvenements = $evenementRepository->count([]);

        // 2) Nombre total de tickets
        $totalTickets = $ticketRepository->count([]);

        // 3) Nombre de villes uniques (champ "lieu" sur Evenement)
        try {
            $villesQuery = $em->createQueryBuilder()
                ->select('COUNT(DISTINCT e.lieu)')
                ->from('App\Entity\Evenement', 'e')
                ->where('e.lieu IS NOT NULL')
                ->getQuery();
            $totalVilles = (int) $villesQuery->getSingleScalarResult();
        } catch (\Exception $e) {
            $totalVilles = 0;
        }

        return $this->render('home/index.html.twig', [
            'evenements'       => $evenements,
            'totalEvenements'  => $totalEvenements,
            'totalTickets'     => $totalTickets,
            'totalVilles'      => $totalVilles,
        ]);
    }

    #[Route('/events', name: 'app_events')]
    public function events(
        Request $request,
        EvenementRepository $evenementRepository
    ): Response {
        $categorie = $request->query->get('categorie');
        $search    = $request->query->get('search');

        if ($search) {
            $evenements = $evenementRepository->searchByTerm($search);
        } elseif ($categorie) {
            $evenements = $evenementRepository->findByCategorie($categorie);
        } else {
            $evenements = $evenementRepository->findActiveEvents();
        }

        $categories = ['Concert', 'Conférence', 'Festival', 'Spectacle', 'Formation', 'Sport'];

        return $this->render('home/events.html.twig', [
            'evenements'        => $evenements,
            'categories'        => $categories,
            'current_categorie' => $categorie,
            'current_search'    => $search,
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_detail')]
    public function eventDetail(int $id, EvenementRepository $evenementRepository): Response
    {
        $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        return $this->render('home/event_detail.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('home/faq.html.twig');
    }
}
