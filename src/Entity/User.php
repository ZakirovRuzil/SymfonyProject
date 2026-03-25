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
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Пользователь с таким email уже существует.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_MODERATOR = 'ROLE_MODERATOR';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Введите email.')]
    #[Assert\Email(message: 'Введите корректный email.')]
    #[Assert\Length(max: 180)]
    private string $email = '';

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [self::ROLE_USER];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Введите отображаемое имя.')]
    #[Assert\Length(min: 2, max: 100)]
    private string $displayName = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Post::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $posts;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    /**
     * @var Collection<int, Section>
     */
    #[ORM\ManyToMany(mappedBy: 'moderators', targetEntity: Section::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $moderatedSections;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->moderatedSections = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->displayName !== '' ? $this->displayName : $this->email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_USER;

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function addRole(string $role): self
    {
        $roles = $this->getRoles();

        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
        }

        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    public function removeRole(string $role): self
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            static fn (string $existingRole): bool => $existingRole !== $role
        ));

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = trim($displayName);

        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): self
    {
        $this->avatarFilename = $avatarFilename;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        $this->posts->removeElement($post);

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        $this->comments->removeElement($comment);

        return $this;
    }

    /**
     * @return Collection<int, Section>
     */
    public function getModeratedSections(): Collection
    {
        return $this->moderatedSections;
    }

    public function addModeratedSection(Section $section): self
    {
        if (!$this->moderatedSections->contains($section)) {
            $this->moderatedSections->add($section);
        }

        return $this;
    }

    public function removeModeratedSection(Section $section): self
    {
        $this->moderatedSections->removeElement($section);

        return $this;
    }
}
