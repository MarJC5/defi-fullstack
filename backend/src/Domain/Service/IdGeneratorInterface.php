<?php

declare(strict_types=1);

namespace App\Domain\Service;

/**
 * Interface for generating unique identifiers.
 *
 * This is a domain abstraction - the actual implementation
 * (UUID, ULID, etc.) is in the Infrastructure layer.
 */
interface IdGeneratorInterface
{
    public function generate(): string;
}
