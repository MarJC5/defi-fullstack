<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class NoRouteFoundException extends \DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(sprintf("No route found from '%s' to '%s'", $from, $to));
    }
}
