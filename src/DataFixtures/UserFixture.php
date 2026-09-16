<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Marie');
        $user->setEmail('marie.dupont@example.com');
        $user->setTelephone('+33612345678');
        $user->setPhoto('https://i.pravatar.cc/150?img=47');
        $user->setRole('chercheur');
        $user->setStatut('actif');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password')
        );
        $user->setDateCreation(new \DateTime());
        $user->setDateModification(new \DateTime());
        $manager->persist($user);

        // Admin
        $admin = new User();
        $admin->setNom('Admin');
        $admin->setPrenom('SAMRE');
        $admin->setEmail('admin@samre.com');
        $admin->setTelephone('+33123456789');
        $admin->setPhoto('https://i.pravatar.cc/150?img=12');
        $admin->setRole('admin');
        $admin->setStatut('actif');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin')
        );
        $admin->setDateCreation(new \DateTime());
        $admin->setDateModification(new \DateTime());
        $manager->persist($admin);

        $manager->flush();
    }
}