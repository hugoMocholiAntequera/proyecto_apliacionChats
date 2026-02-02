<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Entity\Chats;
use App\Entity\Mensajes;
use Doctrine\ORM\EntityManagerInterface;

final class ChatController extends AbstractController
{
    #[Route('/chat/home', name: 'app_chat_home')]
    public function home(Request $request, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        // Obtener token de la sesión o parámetro
        $token = $request->query->get('token') ?? $request->getSession()->get('user_token');
        
        if (!$token) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepo->findOneBy(['token' => $token]);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Guardar token en sesión
        $request->getSession()->set('user_token', $token);

        return $this->render('chat/home.html.twig', [
            'user' => $user,
            'token' => $token
        ]);
    }

    #[Route('/chat/general', name: 'app_chat_general')]
    public function chatGeneral(Request $request, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $token = $request->query->get('token') ?? $request->getSession()->get('user_token');
        
        if (!$token) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepo->findOneBy(['token' => $token]);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Obtener chat general
        $chatGeneral = $em->getRepository(Chats::class)->find(1);
        if (!$chatGeneral) {
            throw $this->createNotFoundException('Chat general no encontrado');
        }

        // Obtener mensajes del chat general
        $mensajes = $em->getRepository(Mensajes::class)->findBy(
            ['chatPerteneciente' => $chatGeneral],
            ['fechaHora' => 'ASC']
        );

        // Obtener usuarios del chat
        $usuarios = $chatGeneral->getUsers();

        return $this->render('chat/general.html.twig', [
            'user' => $user,
            'chat' => $chatGeneral,
            'mensajes' => $mensajes,
            'usuarios' => $usuarios,
            'token' => $token
        ]);
    }

    #[Route('/chat/{chatId}', name: 'app_chat_privado', requirements: ['chatId' => '\d+'])]
    public function chatPrivado(int $chatId, Request $request, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $token = $request->query->get('token') ?? $request->getSession()->get('user_token');
        
        if (!$token) {
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepo->findOneBy(['token' => $token]);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Obtener chat
        $chat = $em->getRepository(Chats::class)->find($chatId);
        if (!$chat) {
            throw $this->createNotFoundException('Chat no encontrado');
        }

        // Verificar que el usuario pertenece al chat
        if (!$chat->getUsers()->contains($user)) {
            throw $this->createAccessDeniedException('No tienes acceso a este chat');
        }

        // Obtener mensajes del chat
        $mensajes = $em->getRepository(Mensajes::class)->findBy(
            ['chatPerteneciente' => $chat],
            ['fechaHora' => 'ASC']
        );

        // Obtener usuarios del chat
        $usuarios = $chat->getUsers();

        return $this->render('chat/privado.html.twig', [
            'user' => $user,
            'chat' => $chat,
            'mensajes' => $mensajes,
            'usuarios' => $usuarios,
            'token' => $token
        ]);
    }
}
