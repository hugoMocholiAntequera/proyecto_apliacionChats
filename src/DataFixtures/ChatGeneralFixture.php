<?php

namespace App\DataFixtures;

use App\Entity\Chats;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use DateTime;

class ChatGeneralFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Verificar si ya existe el chat general
        $chatRepository = $manager->getRepository(Chats::class);
        $existingChat = $chatRepository->findOneBy(['esGeneral' => true]);

        if (!$existingChat) {
            $chatGeneral = new Chats();
            $chatGeneral->setNombre('Chat General');
            $chatGeneral->setEsGeneral(true);
            $chatGeneral->setFechaCreacion(new DateTime());
            
            $manager->persist($chatGeneral);
            $manager->flush();
            
            echo "Chat general creado exitosamente.\n";
        } else {
            echo "Chat general ya existe (ID: {$existingChat->getId()}).\n";
        }
    }
}
