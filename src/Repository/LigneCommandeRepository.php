<?php

namespace App\Repository;

use App\Entity\LigneCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LigneCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LigneCommande::class);
    }

    // Statistiques ventes par événement
    public function getVentesByEvent(): array
    {
        return $this->createQueryBuilder('lc')
            ->select('e.titre as event_titre, SUM(lc.quantite) as total_ventes, SUM(lc.sousTotal) as revenus')
            ->join('lc.typeBillet', 'tb')
            ->join('tb.evenement', 'e')
            ->groupBy('e.id')
            ->orderBy('total_ventes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Ventes par type de billet
    public function getVentesByTicketType(int $eventId): array
    {
        return $this->createQueryBuilder('lc')
            ->select('tb.nomType, SUM(lc.quantite) as total_ventes')
            ->join('lc.typeBillet', 'tb')
            ->where('tb.evenement = :eventId')
            ->setParameter('eventId', $eventId)
            ->groupBy('tb.id')
            ->getQuery()
            ->getResult();
    }

    // ✅ Total de tous les billets vendus (tous événements confondus)
    public function countTotalTicketsSold(): int
    {
        return (int) $this->createQueryBuilder('lc')
            ->select('COALESCE(SUM(lc.quantite), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // ✅ Billets vendus aujourd'hui
    // Utilise lc.commande.dateCommande comme confirmé dans l'entité
    public function countTicketsSoldToday(): int
    {
        $start = (new \DateTime('today'))->setTime(0, 0, 0);
        $end   = (new \DateTime('today'))->setTime(23, 59, 59);

        return (int) $this->createQueryBuilder('lc')
            ->select('COALESCE(SUM(lc.quantite), 0)')
            ->join('lc.commande', 'c')
            ->where('c.dateCommande BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
