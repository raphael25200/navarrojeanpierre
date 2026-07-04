<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\Tableau;
use Doctrine\ORM\Query;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Tableau>
 */
class TableauRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Tableau::class);
    }

    public function findSearch($search): array
    {
        $query = $this->createQueryBuilder('t')
            ->Select('c', 't')
            ->join('t.category', 'c');


        if (!empty($search->q)) {
            $query = $query
                ->andWhere('t.title LIKE :q')
                ->setParameter('q', "%{$search->q}%");
        }

        if (!empty($search->year)) {
            $yearValue = $search->year instanceof \DateTime ? $search->year->format('Y') : $search->year;
            $query = $query
                ->andWhere('SUBSTRING(t.date, 1, 4) = :year')
                ->setParameter('year', $yearValue);
        }


        if (!empty($search->forsale)) {
            $query = $query
                ->andWhere('t.forsale = 1');
        }

        if (!empty($search->categories)) {
            $query = $query
                ->andWhere('c.id IN (:categories)')
                ->setParameter('categories', $search->categories);
        }

        if (!empty($search->orientation)) {
            $query->andWhere('t.orientation = :orientation')
                ->setParameter('orientation', $search->orientation);
        }

        return $query->getQuery()->getResult();
    }

    public function findSearchQuery(SearchData $search): Query
    {
        $query = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->addSelect('SUBSTRING(t.date, 1, 4) AS HIDDEN year');

        if (!empty($search->q)) {
            $query->andWhere('t.title LIKE :q')
                ->setParameter('q', '%' . $search->q . '%');
        }

        if (!empty($search->year)) {
            $query->andWhere('SUBSTRING(t.date, 1, 4) = :year')
                ->setParameter('year', $search->year);
        }

        if (!empty($search->forsale)) {
            $query->andWhere('t.forsale = 1');
        }

        if (!empty($search->categories)) {
            $query->andWhere('c.id IN (:categories)')
                ->setParameter('categories', $search->categories);
        }

        if (!empty($search->orientation)) {
            $query->andWhere('t.orientation = :orientation')
                ->setParameter('orientation', $search->orientation);
        }

        // Ajout du tri dynamique selon $search->order
        if ($search->order === 'asc') {
            $query->orderBy('year', 'ASC');
        } else {
            // Par défaut ou 'desc'
            $query->orderBy('year', 'DESC');
        }
        return $query->getQuery();
    }


    public function paginateTableaux(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->createQueryBuilder('t')
                ->leftJoin('t.category', 'c')
                ->addSelect('c') // pour hydrater la catégorie
                ->orderBy('t.id', 'DESC'),
            $page,
            20,
            [
                'sortFieldAllowList' => ['t.id', 't.title']
            ]
        );
    }

    /**
     * @return Tableau[] Returns an array of Tableau objects
     */
    public function findDateOfCreation(int $date): array
    {
        return $this->createQueryBuilder('t')
            ->select('t', 'c')
            ->where('t.date < :date')
            ->orderBy('t.date', 'ASC')
            ->leftJoin('t.category', 'c')
            ->setMaxResults(10)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function findBySearchCriteria($searchTerm): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->leftJoin('t.category', 'c')
            ->where($qb->expr()->orX(
                $qb->expr()->like('t.title', ':search'),
                $qb->expr()->like('t.description', ':search'),
                $qb->expr()->like('t.keywords', ':search'),
                $qb->expr()->like('c.name', ':search')
            ))
            ->setParameter('search', '%' . $searchTerm . '%');

        return $qb->getQuery()->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Œuvres similaires : même catégorie en priorité, complétées aléatoirement
     * si la catégorie ne fournit pas assez de résultats.
     *
     * @return Tableau[]
     */
    public function findSimilar(Tableau $tableau, int $limit = 4): array
    {
        // 1. IDs des œuvres de la même catégorie (hors œuvre courante)
        $ids = [];
        if ($tableau->getCategory()) {
            $rows = $this->createQueryBuilder('t')
                ->select('t.id')
                ->where('t.category = :category')
                ->andWhere('t.id != :currentId')
                ->setParameter('category', $tableau->getCategory())
                ->setParameter('currentId', $tableau->getId())
                ->getQuery()
                ->getScalarResult();
            $ids = array_column($rows, 'id');
        }

        shuffle($ids);
        $ids = array_slice($ids, 0, $limit);

        // 2. Complément aléatoire si la catégorie ne suffit pas
        if (count($ids) < $limit) {
            $excluded = array_merge($ids, [$tableau->getId()]);
            $rows = $this->createQueryBuilder('t')
                ->select('t.id')
                ->where('t.id NOT IN (:excluded)')
                ->setParameter('excluded', $excluded)
                ->getQuery()
                ->getScalarResult();
            $otherIds = array_column($rows, 'id');

            shuffle($otherIds);
            $ids = array_merge($ids, array_slice($otherIds, 0, $limit - count($ids)));
        }

        if (empty($ids)) {
            return [];
        }

        // 3. Hydratation des œuvres retenues (avec leur catégorie)
        return $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Œuvre précédente dans l'ordre chronologique (date puis id).
     */
    public function findPrevious(Tableau $tableau): ?Tableau
    {
        return $this->findAdjacent($tableau, 'previous');
    }

    /**
     * Œuvre suivante dans l'ordre chronologique (date puis id).
     */
    public function findNext(Tableau $tableau): ?Tableau
    {
        return $this->findAdjacent($tableau, 'next');
    }

    private function findAdjacent(Tableau $tableau, string $direction): ?Tableau
    {
        // Navigation par date : sans date, pas de voisin chronologique.
        if ($tableau->getDate() === null) {
            return null;
        }

        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->addSelect('c')
            ->setParameter('date', $tableau->getDate())
            ->setParameter('id', $tableau->getId())
            ->setMaxResults(1);

        if ($direction === 'next') {
            $qb->andWhere('(t.date > :date OR (t.date = :date AND t.id > :id))')
                ->orderBy('t.date', 'ASC')
                ->addOrderBy('t.id', 'ASC');
        } else {
            $qb->andWhere('(t.date < :date OR (t.date = :date AND t.id < :id))')
                ->orderBy('t.date', 'DESC')
                ->addOrderBy('t.id', 'DESC');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
