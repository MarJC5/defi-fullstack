<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidDistanceException;

final readonly class Distance
{
    private function __construct(
        private float $kilometers
    ) {
    }

    public static function fromKilometers(float $kilometers): self
    {
        if ($kilometers < 0) {
            throw new InvalidDistanceException('Distance cannot be negative');
        }

        return new self($kilometers);
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function kilometers(): float
    {
        return $this->kilometers;
    }

    public function add(self $other): self
    {
        return new self($this->kilometers + $other->kilometers);
    }

    public function isZero(): bool
    {
        return $this->kilometers === 0.0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->kilometers > $other->kilometers;
    }

    public function equals(self $other): bool
    {
        return abs($this->kilometers - $other->kilometers) < 0.0001;
    }

    public function __toString(): string
    {
        return sprintf('%.2f km', $this->kilometers);
    }
}
