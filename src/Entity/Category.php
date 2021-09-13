<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
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
    private bool $rootMenCategory = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $rootWomenCategory = false;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Size", inversedBy="categories", fetch="EAGER")
     */
    private Collection $sizes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Product", mappedBy="category")
     */
    private Collection $products;

    public function __construct(string $title, ?Category $parent)
    {
        $this->title = $title;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
        $this->sizes = new ArrayCollection();
        $this->products = new ArrayCollection();

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

    public function getSizes(): Collection
    {
        return $this->sizes;
    }

    public function addSize(Size $size): void
    {
        if ($this->sizes->contains($size)) {
            return;
        }

        $this->sizes->add($size);
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }


    public function addProduct(Product $product): void
    {
        if ($this->products->contains($product)) {
            return;
        }

        $this->products->add($product);
    }

    public function getSizesOrderedBySort(): Collection
    {
        $criteria = Criteria::create();

        $criteria->orderBy(['sortOrder' => 'asc']);

        return $this->sizes->matching($criteria);
    }

}