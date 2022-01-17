<?php

declare(strict_types=1);

namespace App\Form\BannerForm;

use App\Entity\BannerPlace;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BannerDto
{
    /**
     * @var UploadedFile|null
     */
    public  $image = null;
    /**
     * @var BannerPlace|null
     */
    public $bannerPlace = null;
    /**
     * @var string|null
     */
    public $link = null;
    /**
     * @var string|null
     */
    public $title = null;
    /**
     * @var string|null
     */
    public $description = null;
    /**
     * @var string|null
     */
    public $buttonLabel = null;
}