<?php

namespace App\Acciones\Domain\Exception;

final class AccionNotFoundException extends \RuntimeException
{
    public function __construct(string $uuid)
    {
        parent::__construct("Accion '{$uuid}' not found.");
    }
}
