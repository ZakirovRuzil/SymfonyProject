<?php

namespace App\Entity;

use App\Repository\SectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SectionRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Раздел с таким названием уже существует.')]
class Section
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Введите название раздела.')]
    #[Assert\Length(max: 100)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(mappedBy: 'section', targetEntity: Post::class)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $posts;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'moderatedSections')]
    #[ORM\JoinTable(name: 'section_moderators')]
    #[ORM\OrderBy(['displayName' => 'ASC'])]
    private Collection $moderators;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
        $this->moderators = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;

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
            $post->setSection($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        $this->posts->removeElement($post);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getModerators(): Collection
    {
        return $this->moderators;
    }

    public function addModerator(User $user): self
    {
        if (!$this->moderators->contains($user)) {
            $this->moderators->add($user);
            $user->addModeratedSection($this);
        }

        return $this;
    }

    public function removeModerator(User $user): self
    {
        if ($this->moderators->removeElement($user)) {
            $user->removeModeratedSection($this);
        }

        return $this;
    }

    public function isModerator(User $user): bool
    {
        return $this->moderators->contains($user);
    }
}
