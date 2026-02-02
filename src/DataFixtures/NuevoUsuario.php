<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NuevoUsuario extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Verificar si el usuario root ya existe
        $userRepository = $manager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => 'root@example.com']);

        if (!$existingUser) {
            $email = 'root@example.com';
            $plainPassword = 'root';
            $user = new User();
            $user->setEmail($email);
            $user->setNombre('root');
            $user->setLatitud(0.0);
            $user->setLongitud(0.0);
            $user->setBaneado(false);
            $user->setActivo(true);
            $user->setFechaCreacion(new \DateTime());
            $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            
            // Encriptación de la contraseña
            $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);
            $user->setToken(bin2hex(random_bytes(32)));

            // Añadir al chat general si existe
            $chatGeneral = $manager->getRepository(\App\Entity\Chats::class)->findOneBy(['esGeneral' => true]);
            if ($chatGeneral) {
                $user->addChatPerteneciente($chatGeneral);
            }

            $manager->persist($user);
            $manager->flush();
        }
    }
}
