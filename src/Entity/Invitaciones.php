<?php

namespace App\Entity;

use App\Repository\InvitacionesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvitacionesRepository::class)]
class Invitaciones
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $fechaInvitacion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mensaje = null;

    #[ORM\ManyToOne(inversedBy: 'invitaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $IdUsuarioRemitente = null;

    #[ORM\ManyToOne(inversedBy: 'invitaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $IdUsuarioReceptor = null;

    #[ORM\ManyToOne(inversedBy: 'invitaciones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chats $chatId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaInvitacion(): ?\DateTime
    {
        return $this->fechaInvitacion;
    }

    public function setFechaInvitacion(\DateTime $fechaInvitacion): static
    {
        $this->fechaInvitacion = $fechaInvitacion;

        return $this;
    }

    public function getMensaje(): ?string
    {
        return $this->mensaje;
    }

    public function setMensaje(?string $mensaje): static
    {
        $this->mensaje = $mensaje;

        return $this;
    }

    public function getIdUsuarioRemitente(): ?User
    {
        return $this->IdUsuarioRemitente;
    }

    public function setIdUsuarioRemitente(?User $IdUsuarioRemitente): static
    {
        $this->IdUsuarioRemitente = $IdUsuarioRemitente;

        return $this;
    }

    public function getIdUsuarioReceptor(): ?User
    {
        return $this->IdUsuarioReceptor;
    }

    public function setIdUsuarioReceptor(?User $IdUsuarioReceptor): static
    {
        $this->IdUsuarioReceptor = $IdUsuarioReceptor;

        return $this;
    }

    public function getChatId(): ?Chats
    {
        return $this->chatId;
    }

    public function setChatId(?Chats $chatId): static
    {
        $this->chatId = $chatId;

        return $this;
    }
}
