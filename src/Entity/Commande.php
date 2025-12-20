<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCommande = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(length: 50)]
    private ?string $statutCommande = 'paid_demo';

    #[ORM\Column(length: 50)]
    private ?string $modePaiement = 'demo';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $emailAcheteur = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, cascade: ['persist', 'remove'])]
    private Collection $ligneCommandes;

    #[ORM\OneToOne(mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private ?Recu $recu = null;

    public function __construct()
    {
        $this->ligneCommandes = new ArrayCollection();
        $this->dateCommande = new \DateTimeImmutable();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDateCommande(): ?\DateTimeImmutable
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeImmutable $dateCommande): self
    {
        $this->dateCommande = $dateCommande;
        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): self
    {
        $this->montantTotal = $montantTotal;
        return $this;
    }

    public function getStatutCommande(): ?string
    {
        return $this->statutCommande;
    }

    public function setStatutCommande(string $statutCommande): self
    {
        $this->statutCommande = $statutCommande;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(string $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }

    public function getEmailAcheteur(): ?string
    {
        return $this->emailAcheteur;
    }

    public function setEmailAcheteur(?string $emailAcheteur): self
    {
        $this->emailAcheteur = $emailAcheteur;
        return $this;
    }

    public function getLigneCommandes(): Collection
    {
        return $this->ligneCommandes;
    }

    public function addLigneCommande(LigneCommande $ligneCommande): self
    {
        if (!$this->ligneCommandes->contains($ligneCommande)) {
            $this->ligneCommandes->add($ligneCommande);
            $ligneCommande->setCommande($this);
        }
        return $this;
    }

    public function removeLigneCommande(LigneCommande $ligneCommande): self
    {
        if ($this->ligneCommandes->removeElement($ligneCommande)) {
            if ($ligneCommande->getCommande() === $this) {
                $ligneCommande->setCommande(null);
            }
        }
        return $this;
    }

    public function getRecu(): ?Recu
    {
        return $this->recu;
    }

    public function setRecu(?Recu $recu): self
    {
        if ($recu === null && $this->recu !== null) {
            $this->recu->setCommande(null);
        }

        if ($recu !== null && $recu->getCommande() !== $this) {
            $recu->setCommande($this);
        }

        $this->recu = $recu;
        return $this;
    }
    public function getStatsByEvent(): array
{
    return $this->createQueryBuilder('l')
        ->select('e.id AS event_id, e.titre AS event_title')
        ->addSelect('SUM(l.quantite) AS billets_vendus')
        ->addSelect('SUM(l.quantite * l.prixUnitaire) AS revenus')
        ->join('l.evenement', 'e')
        ->groupBy('e.id')
        ->orderBy('revenus', 'DESC')
        ->getQuery()
        ->getArrayResult();
}


}
