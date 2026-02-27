<?php

namespace App\ActiveDirectory\Domain\Exception;

class OrganizationNotFoundException extends \DomainException
{
    public function __construct(string $code)
    {
        parent::__construct("Organization '{$code}' not found in Active Directory.");
    }
}
