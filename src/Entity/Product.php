<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id", nullable=false)
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer")
     */
    private int $productCode;

    /**
     * @ORM\Column(type="float")
     */
    private float $price;

    /**
     * @ORM\Column
     */
    private string $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $productDetails = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $image = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $lookAfterMe = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $aboutMe = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $image1 = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $image2 = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $image3 = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="products")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     */
    private Category $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Brand", inversedBy="products")
     * @ORM\JoinColumn(onDelete="RESTRICT")
     */
    private ?Brand $brand = null;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\SearchWord", inversedBy="products", fetch="EAGER")
     */
    private Collection $searchWords;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Size", inversedBy="products", fetch="EAGER")
     */
    private Collection $sizes;

    public function __construct(int $productCode, float $price, string $title, Category $category)
    {
        $this->productCode = $productCode;
        $this->price = $price;
        $this->title = $title;
        $this->category = $category;
        $this->searchWords = new ArrayCollection();
        $this->sizes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getProductCode(): int
    {
        return $this->productCode;
    }

    public function setProductCode(int $productCode): void
    {
        $this->productCode = $productCode;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getProductDetails(): ?string
    {
        return $this->productDetails;
    }

    public function setProductDetails(?string $productDetails): void
    {
        $this->productDetails = $productDetails;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getLookAfterMe(): ?string
    {
        return $this->lookAfterMe;
    }

    public function setLookAfterMe(?string $lookAfterMe): void
    {
        $this->lookAfterMe = $lookAfterMe;
    }

    public function getAboutMe(): ?string
    {
        return $this->aboutMe;
    }

    public function setAboutMe(?string $aboutMe): void
    {
        $this->aboutMe = $aboutMe;
    }

    public function getImage1(): ?string
    {
        return $this->image1;
    }

    public function setImage1(?string $image1): void
    {
        $this->image1 = $image1;
    }

    public function getImage2(): ?string
    {
        return $this->image2;
    }

    public function setImage2(?string $image2): void
    {
        $this->image2 = $image2;
    }

    public function getImage3(): ?string
    {
        return $this->image3;
    }

    public function setImage3(?string $image3): void
    {
        $this->image3 = $image3;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): void
    {
        $this->brand = $brand;
    }

    public function getSearchWords(): Collection
    {
        return $this->searchWords;
    }

    public function addSearchWord(SearchWord $searchWord): void
    {
        if ($this->searchWords->contains($searchWord)) {
            return;
        }

        $this->searchWords->add($searchWord);
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

    public function deleteSizes()
    {
        $this->sizes = new ArrayCollection();
    }

}