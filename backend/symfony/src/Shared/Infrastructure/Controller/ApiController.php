<?php

namespace App\Shared\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class ApiController extends AbstractController
{
    public function __construct(
        protected readonly MessageBusInterface $commandBus,
        #[Target('query.bus')] protected readonly MessageBusInterface $queryBus,
    ) {}
}
