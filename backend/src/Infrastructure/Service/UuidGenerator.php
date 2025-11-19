<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * UUID v4 implementation of IdGeneratorInterface.
 *
 * Uses Symfony's Uuid component to generate RFC 4122 compliant UUIDs.
 */
class UuidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
