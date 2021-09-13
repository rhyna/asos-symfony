<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Size
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
     * @ORM\Column
     */
    private string $normalizedTitle;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $sortOrder = null;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Category", mappedBy="sizes")
     */
    private Collection $categories;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Product", mappedBy="sizes")
     */
    private Collection $products;

    public function __construct(string $title)
    {
        $this->title = $title;

        $lowerCaseTitle = mb_strtolower($title);
        $normalizedTitle = str_replace(' ', '', $lowerCaseTitle);
        $this->normalizedTitle = (string)$normalizedTitle;

        $this->categories = new ArrayCollection();
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

    public function getNormalizedTitle(): string
    {
        return $this->normalizedTitle;
    }

    public function setNormalizedTitle(string $normalizedTitle): void
    {
        $this->normalizedTitle = $normalizedTitle;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): void
    {
        if ($this->categories->contains($category)) {
            return;
        }

        $this->categories->add($category);
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function setProducts(Product $product): void
    {
        if ($this->products->contains($product)) {
            return;
        }

        $this->products->add($product);
    }

}