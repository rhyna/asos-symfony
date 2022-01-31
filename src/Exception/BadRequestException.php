<?php

declare(strict_types=1);

namespace App\Exception;

class BadRequestException extends AsosException
{
    protected $code = 400;
}