<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        // Récupérer les événements actifs
        $evenements = $evenementRepository->findActiveEvents();

        return $this->render('home/index.html.twig', [
            'evenements' => $evenements,
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

        $categories = ['Concert', 'Conférence', 'Festival', 'Spectacle', 'Formation'];

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
