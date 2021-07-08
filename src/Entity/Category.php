<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id", nullable=false)
     */
    private ?int $id = null;

    /**
     * @ORM\Column
     */
    private string $title;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="children")
     */
    private ?Category $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Category", mappedBy="parent")
     */
    private Collection $children;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $image = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $rootMenCategory;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $rootWomenCategory;

    public function __construct(string $title, ?int $parent, bool $rootMenCategory, bool $rootWomenCategory)
    {
        $this->title = $title;
        $this->parent = $parent;
        $this->rootMenCategory = $rootMenCategory;
        $this->rootWomenCategory = $rootWomenCategory;

        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): void
    {
        $this->parent = $parent;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Category $child): void
    {
        if ($this->children->contains($child)) {
            return;
        }

        $this->children->add($child);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function isRootMenCategory(): bool
    {
        return $this->rootMenCategory;
    }

    public function setRootMenCategory(bool $rootMenCategory): void
    {
        $this->rootMenCategory = $rootMenCategory;
    }

    public function isRootWomenCategory(): bool
    {
        return $this->rootWomenCategory;
    }

    public function setRootWomenCategory(bool $rootWomenCategory): void
    {
        $this->rootWomenCategory = $rootWomenCategory;
    }
}