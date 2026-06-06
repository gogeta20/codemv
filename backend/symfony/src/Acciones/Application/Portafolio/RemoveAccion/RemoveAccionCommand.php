<?php

namespace App\Acciones\Application\Portafolio\RemoveAccion;

final readonly class RemoveAccionCommand
{
    public function __construct(public string $entryUuid) {}
}
