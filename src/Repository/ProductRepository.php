<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i');

        if (isset($criteria['category'])) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $criteria['category']);
        }
        if (isset($criteria['price'])) {
            if (isset($criteria['price']['>='])) {
                $qb->andWhere('p.price >= :minPrice')
                    ->setParameter('minPrice', $criteria['price']['>=']);
            }
            if (isset($criteria['price']['<='])) {
                $qb->andWhere('p.price <= :maxPrice')
                    ->setParameter('maxPrice', $criteria['price']['<=']);
            }
        }
        if (isset($criteria['quantity'])) {
            if (isset($criteria['quantity']['>='])) {
                $qb->andWhere('p.quantity >= :minQuantity')
                    ->setParameter('minQuantity', $criteria['quantity']['>=']);
            }
            if (isset($criteria['quantity']['<='])) {
                $qb->andWhere('p.quantity <= :maxQuantity')
                    ->setParameter('maxQuantity', $criteria['quantity']['<=']);
            }
        }

        return $qb->getQuery()->getResult();
    }


    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
