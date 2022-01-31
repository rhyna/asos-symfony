<?php

declare(strict_types=1);

namespace App\Exception;

class ValidationErrorException extends AsosException
{
    protected $code = 422;
}