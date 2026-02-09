<?php

namespace App\Repository;

use App\Entity\AdminPharmacy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdminPharmacy>
 *
 * @method AdminPharmacy|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminPharmacy|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminPharmacy[]    findAll()
 * @method AdminPharmacy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminPharmacyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminPharmacy::class);
    }

//    /**
//     * @return AdminPharmacy[] Returns an array of AdminPharmacy objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AdminPharmacy
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
