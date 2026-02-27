<?php

namespace App\Study\Domain\Exception;

class CategoryAlreadyExistsException extends \DomainException
{
    public function __construct(string $slug)
    {
        parent::__construct("Category '{$slug}' already exists.");
    }
}
