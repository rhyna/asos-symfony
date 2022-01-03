<?php

declare(strict_types=1);

namespace App\Form\BannerAddForm;

use App\Entity\BannerPlace;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Специальная сущность, которая будет хранить в себе данные формы
 * Она используется для наполнения данными из POST-запроса
 */
class BannerDto
{
    /**
     * @var UploadedFile|null
     */
    public $image = null;
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