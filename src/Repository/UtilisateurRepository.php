<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 *
 * @method Utilisateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utilisateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utilisateur[]    findAll()
 * @method Utilisateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function save(Utilisateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Utilisateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setMdp($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * Trouve tous les conducteurs
     * 
     * @return Utilisateur[] Les utilisateurs qui sont des conducteurs
     */
    public function findConducteurs(): array
    {
        try {
            return $this->createQueryBuilder('u')
                ->join('u.role', 'r')
                ->where('r.code = :role')
                ->setParameter('role', 'CONDUCTEUR')
                ->orderBy('u.nom', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Trouve les utilisateurs par type (conducteur)
     * 
     * @param string $type Le type d'utilisateur à rechercher (conducteur)
     * @return Utilisateur[] Les utilisateurs du type spécifié
     */
    public function findByRoles(array $roles): array
    {
        // Puisque l'entité Utilisateur n'a pas de champ 'roles', 
        // nous allons chercher les conducteurs par leur type
        return $this->createQueryBuilder('u')
            ->where('u.type = :type')
            ->setParameter('type', 'conducteur')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Count users by role code
     * 
     * @param string $roleCode The role code to count
     * @return int The number of users with the given role
     */
    public function countByRole(string $roleCode): int
    {
        try {
            return $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roleCode = :roleCode')
                ->setParameter('roleCode', $roleCode)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Find the most recently registered users
     * 
     * @param int $limit The maximum number of users to return
     * @return Utilisateur[] The most recently registered users
     */
    public function findRecent(int $limit = 5): array
    {
        try {
            return $this->createQueryBuilder('u')
                ->orderBy('u.createdAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Count active users (logged in within the last 30 days)
     * 
     * @return int The number of active users
     */
    public function countActive(): int
    {
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        try {
            return $this->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.dernierConnexion >= :thirtyDaysAgo')
                ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            // If dernierConnexion doesn't exist or other error, return total count
            return $this->count([]);
        }
    }

//    /**
//     * @return Utilisateur[] Returns an array of Utilisateur objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Utilisateur
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
} 