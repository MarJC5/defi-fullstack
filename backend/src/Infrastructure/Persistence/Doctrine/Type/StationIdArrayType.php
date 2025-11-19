<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\ValueObject\StationId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * Doctrine type for array of StationId Value Objects.
 */
class StationIdArrayType extends JsonType
{
    public const NAME = 'station_id_array';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        $array = parent::convertToPHPValue($value, $platform);

        if ($array === null || !is_array($array)) {
            return [];
        }

        return array_map(
            fn(string $s) => StationId::fromString($s),
            $array
        );
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $strings = array_map(
                fn($item) => $item instanceof StationId ? $item->value() : $item,
                $value
            );
            return parent::convertToDatabaseValue($strings, $platform);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
