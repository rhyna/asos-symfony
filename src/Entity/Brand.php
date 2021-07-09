<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Brand
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
     * @ORM\Column(nullable=true, type="text")
     */
    private ?string $descriptionMen = null;

    /**
     * @ORM\Column(nullable=true, type="text")
     */
    private ?string $descriptionWomen = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Product", mappedBy="brand")
     */
    private Collection $products;

    public function __construct(string $title)
    {
        $this->title = $title;
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

    public function getDescriptionMen(): ?string
    {
        return $this->descriptionMen;
    }

    public function setDescriptionMen(?string $descriptionMen): void
    {
        $this->descriptionMen = $descriptionMen;
    }

    public function getDescriptionWomen(): ?string
    {
        return $this->descriptionWomen;
    }

    public function setDescriptionWomen(?string $descriptionWomen): void
    {
        $this->descriptionWomen = $descriptionWomen;
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



}