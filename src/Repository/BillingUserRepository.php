<?php

namespace App\Repository;

use App\Entity\BillingUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BillingUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method BillingUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method BillingUser[]    findAll()
 * @method BillingUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BillingUserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BillingUser::class);
    }

    public function convertIdToEmail($ids)
    {
        $emails = $this->createQueryBuilder('u')
            ->select('u.email')
            ->where('u.id IN (:ids)')
            ->setParameter("ids", $ids)
            ->getQuery()
            ->getResult();

        return $emails;
    }
}
