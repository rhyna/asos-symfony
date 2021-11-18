<?php

declare(strict_types=1);

namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SearchWordRepository")
 */
class SearchWord
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
    private string $word;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Product", mappedBy="searchWords")
     */
    private Collection $products;

    public function __construct(string $word)
    {
        $this->word = $word;
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function setWord(string $word): void
    {
        $this->word = $word;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }
}