<?php

namespace App\Entity;

use App\Repository\MensajesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MensajesRepository::class)]
class Mensajes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenido = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagen = null;

    #[ORM\Column]
    private ?\DateTime $fechaHora = null;

    #[ORM\ManyToOne(inversedBy: 'mensajes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $nombreUsuario = null;

    #[ORM\ManyToOne(inversedBy: 'mensajes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chats $chatPerteneciente = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenido(): ?string
    {
        return $this->contenido;
    }

    public function setContenido(string $contenido): static
    {
        $this->contenido = $contenido;

        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): static
    {
        $this->imagen = $imagen;

        return $this;
    }

    public function getFechaHora(): ?\DateTime
    {
        return $this->fechaHora;
    }

    public function setFechaHora(\DateTime $fechaHora): static
    {
        $this->fechaHora = $fechaHora;

        return $this;
    }

    public function getNombreUsuario(): ?User
    {
        return $this->nombreUsuario;
    }

    public function setNombreUsuario(?User $nombreUsuario): static
    {
        $this->nombreUsuario = $nombreUsuario;

        return $this;
    }

    public function getChatPerteneciente(): ?Chats
    {
        return $this->chatPerteneciente;
    }

    public function setChatPerteneciente(?Chats $chatPerteneciente): static
    {
        $this->chatPerteneciente = $chatPerteneciente;

        return $this;
    }
}
