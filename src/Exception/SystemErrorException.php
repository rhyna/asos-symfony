<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

class SystemErrorException extends Exception
{
    protected $message = 'A system error occurred';
}