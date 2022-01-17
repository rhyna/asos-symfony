<?php

declare(strict_types=1);

namespace App\Form\CategoryForm;

use App\Entity\Category;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CategoryDto
{
    public ?string $title = null;
    public ?Category $parentCategory = null;
    public ?UploadedFile $image = null;
    public ?string $description = null;
}