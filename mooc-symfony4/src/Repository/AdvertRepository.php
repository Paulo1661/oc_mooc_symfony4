<?php

namespace App\Repository;

use App\Entity\Advert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Advert|null find($id, $lockMode = null, $lockVersion = null)
 * @method Advert|null findOneBy(array $criteria, array $orderBy = null)
 * @method Advert[]    findAll()
 * @method Advert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdvertRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Advert::class);
    }

    // /**
    //  * @return Advert[] Returns an array of Advert objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
    
    /**
     * @return Advert[] Returns an array of Advert objects
     */
    
    public function findByAuthorAndDate($author, $year)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.author = :author')
            ->setParameter('author', $author)
            ->andWhere('a.date < :year')
            ->setParameter('date', $year)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function whereCurrentYear(QueryBuilder $qb)
    {
      $qb
        ->andWhere('a.date BETWEEN :start AND :end')
        ->setParameter('start', new \Datetime(date('Y').'-01-01'))  // Date entre le 1er janvier de cette année
        ->setParameter('end',   new \Datetime(date('Y').'-12-31'))  // Et le 31 décembre de cette année
      ;
    }

    public function myFind()
    {
      $qb = $this->createQueryBuilder('a');

      // On peut ajouter ce qu'on veut avant
      $qb
        ->where('a.author = :author')
        ->setParameter('author', 'Marine')
      ;

      // On applique notre condition sur le QueryBuilder
      $this->whereCurrentYear($qb);

      // On peut ajouter ce qu'on veut après
      $qb->orderBy('a.date', 'DESC');

      return $qb
        ->getQuery()
        ->getResult()
      ;
    }


    /**
     * @return Advert[] Returns an array of Advert objects
     */
    public function AdvertWithCategories(array $categoryNames)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.cartegories','cat','WITH','cat.name IN(:categoryNames)')            
            ->addSelect('cat')
            ->setParameter('categoryNames', $categoryNames)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * @return Advert[] Returns an array of Advert objects
     */
    public function getAdverts($page, $nbPerPage)
    {
        $query = $this->createQueryBuilder('a');
        $query
            ->leftJoin('a.image','img')
            ->addSelect('img')
            ->leftJoin('a.categories','cat')
            ->addSelect('cat')
            ->orderBy('a.date', 'DESC')
            // On définit l'annonce à partir de laquelle commencer la liste
            ->setFirstResult(($page-1) * $nbPerPage)
            // Ainsi que le nombre d'annonce à afficher sur une page
            ->setMaxResults($nbPerPage)
            ->getQuery()
        ;
        // Enfin, on retourne l'objet Paginator correspondant à la requête construite
        return new Paginator($query, true);
    }


    /*
    public function findOneBySomeField($value): ?Advert
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
