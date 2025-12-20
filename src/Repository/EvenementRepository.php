<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    // Trouver les événements actifs
    public function findActiveEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'actif')
            ->andWhere('e.dateEvenement >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateEvenement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Filtrer par catégorie
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.categorie = :categorie')
            ->andWhere('e.statut = :statut')
            ->setParameter('categorie', $categorie)
            ->setParameter('statut', 'actif')
            ->orderBy('e.dateEvenement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Compter événements actifs
    public function countActiveEvents(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.statut = :statut')
            ->setParameter('statut', 'actif')
            ->getQuery()
            ->getSingleScalarResult();
    }
    // Met à jour les statuts en fonction de la date de l'événement
public function updateStatusesByDate(): void
{
    $now = new \DateTimeImmutable();

    $qb = $this->createQueryBuilder('e')
        ->update()
        ->set('e.statut', ':inactif')
        ->where('e.dateEvenement < :now')
        ->andWhere('e.statut = :actif')
        ->setParameter('inactif', 'inactif')
        ->setParameter('actif', 'actif')
        ->setParameter('now', $now);

    $qb->getQuery()->execute();
}

// Récupérer tous les événements, triés par date
public function findAllOrderByDate(): array
{
    return $this->createQueryBuilder('e')
        ->orderBy('e.dateEvenement', 'ASC')
        ->getQuery()
        ->getResult();
}

}
