<?php

namespace App\Entity;

use App\Repository\ChatsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChatsRepository::class)]
class Chats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $fechaCreacion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'boolean')]
    private bool $esGeneral = false;

    /**
     * @var Collection<int, Mensajes>
     */
    #[ORM\OneToMany(targetEntity: Mensajes::class, mappedBy: 'chatPerteneciente')]
    private Collection $mensajes;

    /**
     * @var Collection<int, Invitaciones>
     */
    #[ORM\OneToMany(targetEntity: Invitaciones::class, mappedBy: 'chatId')]
    private Collection $invitaciones;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'chatPerteneciente')]
    private Collection $users;

    public function __construct()
    {
        $this->mensajes = new ArrayCollection();
        $this->invitaciones = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaCreacion(): ?\DateTime
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTime $fechaCreacion): static
    {
        $this->fechaCreacion = $fechaCreacion;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function isEsGeneral(): bool
    {
        return $this->esGeneral;
    }

    public function setEsGeneral(bool $esGeneral): static
    {
        $this->esGeneral = $esGeneral;

        return $this;
    }

    /**
     * @return Collection<int, Mensajes>
     */
    public function getMensajes(): Collection
    {
        return $this->mensajes;
    }

    public function addMensaje(Mensajes $mensaje): static
    {
        if (!$this->mensajes->contains($mensaje)) {
            $this->mensajes->add($mensaje);
            $mensaje->setChatPerteneciente($this);
        }

        return $this;
    }

    public function removeMensaje(Mensajes $mensaje): static
    {
        if ($this->mensajes->removeElement($mensaje)) {
            // set the owning side to null (unless already changed)
            if ($mensaje->getChatPerteneciente() === $this) {
                $mensaje->setChatPerteneciente(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invitaciones>
     */
    public function getInvitaciones(): Collection
    {
        return $this->invitaciones;
    }

    public function addInvitacione(Invitaciones $invitacione): static
    {
        if (!$this->invitaciones->contains($invitacione)) {
            $this->invitaciones->add($invitacione);
            $invitacione->setChatId($this);
        }

        return $this;
    }

    public function removeInvitacione(Invitaciones $invitacione): static
    {
        if ($this->invitaciones->removeElement($invitacione)) {
            // set the owning side to null (unless already changed)
            if ($invitacione->getChatId() === $this) {
                $invitacione->setChatId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addChatPerteneciente($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeChatPerteneciente($this);
        }

        return $this;
    }
}
