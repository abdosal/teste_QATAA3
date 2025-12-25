<?php

namespace App\Controller;

// src/Controller/ChatController.php

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/chat-api', name: 'app_chat_api', methods: ['POST'])]
    public function ask(Request $request, EvenementRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = strtolower($data['message'] ?? '');

        // Exemple très simple d’“intelligence” côté backend
        if (str_contains($message, 'concert')) {
            $events = $repo->findByCategorieLimit('Concert', 3); // à implémenter
            $names = array_map(fn($e) => $e->getTitre(), $events);
            $reply = $names
                ? 'Voici quelques concerts à venir : '.implode(', ', $names)
                : 'Pas de concerts trouvés pour le moment.';
        } else {
            $reply = 'Tu peux me demander par exemple : "Montre-moi les concerts" ou "Quels festivals ce week-end ?".';
        }

        return new JsonResponse(['reply' => $reply]);
    }
}
