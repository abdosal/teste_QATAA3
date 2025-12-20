<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    // Commandes d'un client
    public function findByClient(int $clientId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Statistiques - Ventes du jour
    public function countTodaySales(): int
    {
        $today = new \DateTime('today');
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.dateCommande >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Chiffre d'affaires total (mode démo)
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.montantTotal)')
            ->where('c.statutCommande = :statut')
            ->setParameter('statut', 'paid_demo')
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result ? (float)$result : 0;
    }
// Revenus par période (test sans filtre de statut)
public function getRevenueByPeriod(\DateTime $startDate, \DateTime $endDate): array
{
    return $this->createQueryBuilder('c')
        ->where('c.dateCommande BETWEEN :start AND :end')
        ->setParameter('start', $startDate)
        ->setParameter('end', $endDate)
        ->orderBy('c.dateCommande', 'ASC')
        ->getQuery()
        ->getResult();
}
public function findLastOrders(int $limit = 5): array
{
    return $this->createQueryBuilder('c')
        ->orderBy('c.dateCommande', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}


}
