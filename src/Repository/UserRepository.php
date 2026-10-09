<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Permet la connexion avec l'email OU le numéro de téléphone (avec ou sans indicatif)
     */
    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        // 1. Recherche directe par email (insensible à la casse)
        $user = $this->createQueryBuilder('u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', strtolower($identifier))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            return $user;
        }

        // 2. Recherche directe par téléphone exact
        $user = $this->createQueryBuilder('u')
            ->where('u.telephone = :tel')
            ->setParameter('tel', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            return $user;
        }

        // 3. Recherche flexible par chiffres (ex: saisie '70401912' vs en base '+228 70 40 19 12')
        $digitsOnly = preg_replace('/\D/', '', $identifier);
        if (!empty($digitsOnly) && strlen($digitsOnly) >= 6) {
            $searchChars = str_split($digitsOnly);
            $flexiblePattern = '%' . implode('%', $searchChars) . '%';

            $candidates = $this->createQueryBuilder('u')
                ->where('u.telephone LIKE :flexPattern')
                ->setParameter('flexPattern', $flexiblePattern)
                ->setMaxResults(25)
                ->getQuery()
                ->getResult();

            foreach ($candidates as $candidate) {
                $candDigits = preg_replace('/\D/', '', (string) $candidate->getTelephone());
                if (
                    $candDigits === $digitsOnly ||
                    str_ends_with($candDigits, $digitsOnly) ||
                    str_ends_with($digitsOnly, $candDigits)
                ) {
                    return $candidate;
                }
            }

            // Fallback complet sur tous les utilisateurs ayant un téléphone
            $allUsers = $this->createQueryBuilder('u')
                ->where('u.telephone IS NOT NULL AND u.telephone != \'\'')
                ->setMaxResults(150)
                ->getQuery()
                ->getResult();

            foreach ($allUsers as $candidate) {
                $candDigits = preg_replace('/\D/', '', (string) $candidate->getTelephone());
                if (!empty($candDigits)) {
                    if (
                        $candDigits === $digitsOnly ||
                        str_ends_with($candDigits, $digitsOnly) ||
                        str_ends_with($digitsOnly, $candDigits)
                    ) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }
}
