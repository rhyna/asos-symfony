<?php

declare(strict_types=1);

namespace App\Exception;

class SystemErrorException extends AsosException
{
    protected $message = 'A system error occurred';
}