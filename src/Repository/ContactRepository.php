<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository implements ContactRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findOneByPPIdentifier(string $ppIdentifier): ?Contact {
        $qb = $this->createQueryBuilder('c');

        return $qb->where($qb->expr()->eq('c.ppIdentifier', ':ppIdentifier'))
            ->setParameter('ppIdentifier', $ppIdentifier)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function listAllContactsToBeDeleted(int $offset, int $limit): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->lte('c.updatedAt', ':date'))
            ->setParameter('date', (new \DateTimeImmutable('-1 week'))->format(DATE_ATOM))
            ->andWhere($qb->expr()->isNull('c.deletedAt'))
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        return $qb->getQuery()->getResult();
    }

    public function save(Contact $contact, bool $flush = false, bool $clear = false): void
    {
        $this->getEntityManager()->persist($contact);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
        if ($clear) {
            $this->getEntityManager()->clear();
        }
    }
}
