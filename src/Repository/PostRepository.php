<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Section;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return list<Post>
     */
    public function findBySectionOrdered(Section $section): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.section = :section')
            ->setParameter('section', $section)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
