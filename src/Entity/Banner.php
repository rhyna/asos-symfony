<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Banner
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id", nullable=false)
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\BannerPlace", inversedBy="banner")
     */
    private ?BannerPlace $bannerPlace = null;

    /**
     * @ORM\Column
     */
    private string $image;

    /**
     * @ORM\Column
     */
    private string $link;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $title = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $description = null;

    /**
     * @ORM\Column(nullable=true)
     */
    private ?string $buttonLabel = null;

    public function __construct(string $image, string $link)
    {
        $this->image = $image;
        $this->link = $link;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBannerPlace(): ?BannerPlace
    {
        return $this->bannerPlace;
    }

    public function setBannerPlace(?BannerPlace $bannerPlace): void
    {
        $this->bannerPlace = $bannerPlace;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getButtonLabel(): ?string
    {
        return $this->buttonLabel;
    }

    public function setButtonLabel(?string $buttonLabel): void
    {
        $this->buttonLabel = $buttonLabel;
    }



}