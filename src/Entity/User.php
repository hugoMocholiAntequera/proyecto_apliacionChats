<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column]
    private ?float $latitud = null;

    #[ORM\Column]
    private ?float $longitud = null;

    #[ORM\Column]
    private ?bool $baneado = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biografia = null;

    #[ORM\Column]
    private ?\DateTime $fechaCreacion = null;

    /**
     * @var Collection<int, Mensajes>
     */
    #[ORM\OneToMany(targetEntity: Mensajes::class, mappedBy: 'nombreUsuario')]
    private Collection $mensajes;

    /**
     * @var Collection<int, Invitaciones>
     */
    #[ORM\OneToMany(targetEntity: Invitaciones::class, mappedBy: 'IdUsuarioRemitente')]
    private Collection $invitaciones;

    /**
     * @var Collection<int, Chats>
     */
    #[ORM\ManyToMany(targetEntity: Chats::class, inversedBy: 'users')]
    private Collection $chatPerteneciente;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'users')]
    private Collection $usuariosBloqueados;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'usuariosBloqueados')]
    private Collection $users;

    #[ORM\Column]
    private ?bool $activo = null;

    public function __construct()
    {
        $this->mensajes = new ArrayCollection();
        $this->invitaciones = new ArrayCollection();
        $this->chatPerteneciente = new ArrayCollection();
        $this->usuariosBloqueados = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getLatitud(): ?float
    {
        return $this->latitud;
    }

    public function setLatitud(float $latitud): static
    {
        $this->latitud = $latitud;

        return $this;
    }

    public function getLongitud(): ?float
    {
        return $this->longitud;
    }

    public function setLongitud(float $longitud): static
    {
        $this->longitud = $longitud;

        return $this;
    }

    public function isBaneado(): ?bool
    {
        return $this->baneado;
    }

    public function setBaneado(bool $baneado): static
    {
        $this->baneado = $baneado;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBiografia(): ?string
    {
        return $this->biografia;
    }

    public function setBiografia(?string $biografia): static
    {
        $this->biografia = $biografia;

        return $this;
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
            $mensaje->setNombreUsuario($this);
        }

        return $this;
    }

    public function removeMensaje(Mensajes $mensaje): static
    {
        if ($this->mensajes->removeElement($mensaje)) {
            // set the owning side to null (unless already changed)
            if ($mensaje->getNombreUsuario() === $this) {
                $mensaje->setNombreUsuario(null);
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
            $invitacione->setIdUsuarioRemitente($this);
        }

        return $this;
    }

    public function removeInvitacione(Invitaciones $invitacione): static
    {
        if ($this->invitaciones->removeElement($invitacione)) {
            // set the owning side to null (unless already changed)
            if ($invitacione->getIdUsuarioRemitente() === $this) {
                $invitacione->setIdUsuarioRemitente(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Chats>
     */
    public function getChatPerteneciente(): Collection
    {
        return $this->chatPerteneciente;
    }

    public function addChatPerteneciente(Chats $chatPerteneciente): static
    {
        if (!$this->chatPerteneciente->contains($chatPerteneciente)) {
            $this->chatPerteneciente->add($chatPerteneciente);
        }

        return $this;
    }

    public function removeChatPerteneciente(Chats $chatPerteneciente): static
    {
        $this->chatPerteneciente->removeElement($chatPerteneciente);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getUsuariosBloqueados(): Collection
    {
        return $this->usuariosBloqueados;
    }

    public function addUsuariosBloqueado(self $usuariosBloqueado): static
    {
        if (!$this->usuariosBloqueados->contains($usuariosBloqueado)) {
            $this->usuariosBloqueados->add($usuariosBloqueado);
        }

        return $this;
    }

    public function removeUsuariosBloqueado(self $usuariosBloqueado): static
    {
        $this->usuariosBloqueados->removeElement($usuariosBloqueado);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(self $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addUsuariosBloqueado($this);
        }

        return $this;
    }

    public function removeUser(self $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeUsuariosBloqueado($this);
        }

        return $this;
    }

    public function isActivo(): ?bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): static
    {
        $this->activo = $activo;

        return $this;
    }
}
