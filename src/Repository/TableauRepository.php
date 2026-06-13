<?php

namespace App\Repository;

use App\Data\SearchData;
use App\Entity\Tableau;
use Doctrine\ORM\Query;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

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
}
