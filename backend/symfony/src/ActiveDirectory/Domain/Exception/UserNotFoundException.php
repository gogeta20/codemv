<?php

namespace App\ActiveDirectory\Domain\Exception;

class UserNotFoundException extends \DomainException
{
    public function __construct(string $samAccountName)
    {
        parent::__construct("User '{$samAccountName}' not found in Active Directory.");
    }
}
