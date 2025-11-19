<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\Distance;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine type for Distance Value Object.
 */
class DistanceType extends Type
{
    public const NAME = 'distance';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getFloatDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Distance
    {
        if ($value === null) {
            return null;
        }

        return Distance::fromKilometers((float) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Distance) {
            return $value->kilometers();
        }

        return (float) $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
