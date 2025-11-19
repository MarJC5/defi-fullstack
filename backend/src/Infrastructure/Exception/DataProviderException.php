<?php

declare(strict_types=1);

namespace App\Infrastructure\Exception;

/**
 * Exception thrown when a data provider fails to retrieve or parse data.
 */
class DataProviderException extends \RuntimeException
{
    public static function cannotReadFile(string $path): self
    {
        return new self("Cannot read data file: {$path}");
    }

    public static function invalidJson(string $path, string $error): self
    {
        return new self("Invalid JSON in data file '{$path}': {$error}");
    }
}
