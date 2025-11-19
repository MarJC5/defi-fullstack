<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class InvalidStationIdException extends \InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
