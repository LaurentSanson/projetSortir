<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @method Sortie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sortie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sortie[]    findAll()
 * @method Sortie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function search($site, $search, $dateDebut, $dateFin, $checkbox1, $checkbox2, $checkbox3, $checkbox4, $user)
    {
        $qb = $this->createQueryBuilder('i');
        $qb->andWhere('i.dateDebut > :olderThanAMonth')
            ->setParameter('olderThanAMonth', new \DateTime('-30 days'));
        if ($site){
            $qb->andWhere('i.site = :site')
                ->setParameter('site', $site);
        }

        $qb->andWhere('i.nom LIKE :search')
            ->setParameter('search', "%" . $search . "%");
        if ($dateDebut) {
            $qb->andWhere('i.dateDebut BETWEEN :debut AND :fin')
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin);
        }

        if ($checkbox1 == "on") {
            $qb->andWhere('i.organisateur = :user')
                ->setParameter('user', $user);
        }
        if ($checkbox2 == "on") {
            $qb->innerJoin('i.participants', 'participants')
                ->andWhere(':user MEMBER OF i.participants')
                ->andWhere('participants MEMBER OF i.participants')
                ->setParameter('user', $user);
        }
        if ($checkbox3 == "on") {
            $qb->innerJoin('i.participants', 'parts')
                ->andWhere(':user NOT MEMBER OF i.participants')
                ->andWhere('parts MEMBER OF i.participants')
                ->setParameter('user', $user);
        }
        if ($checkbox4 == 'on') {
            $qb->andWhere('i.dateDebut < :today')
                ->setParameter('today', new \DateTime('now'));
        }
        $query = $qb->getQuery();
        return $query->getResult();
    }


    // /**
    //  * @return Sortie[] Returns an array of Sortie objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Sortie
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
