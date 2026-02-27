<?php

namespace App\Study\Domain\Exception;

class StudyNotFoundException extends \DomainException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Study '{$uuid}' not found.");
    }
}
