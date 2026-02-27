<?php

namespace App\ActiveDirectory\Domain\ValueObject;

final readonly class OrganizationCode
{
    public function __construct(
        private string $value,
    ) {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Organization code cannot be empty.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
