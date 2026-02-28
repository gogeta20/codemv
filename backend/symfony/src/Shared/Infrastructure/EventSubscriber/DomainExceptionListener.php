<?php

namespace App\Shared\Infrastructure\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Centralizes domain exception → HTTP response mapping for all verticals.
 *
 * Convention-based: exceptions extending DomainException with specific keywords
 * in their class name get mapped automatically. Custom mappings can be added
 * via the EXCEPTION_MAP constant.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class DomainExceptionListener
{
    /**
     * Explicit exception class → HTTP status code mapping.
     * Add any domain exception from any vertical here.
     *
     * @var array<class-string, int>
     */
    private const EXCEPTION_MAP = [
        // Study
        \App\Study\Domain\Exception\StudyNotFoundException::class => Response::HTTP_NOT_FOUND,
        \App\Study\Domain\Exception\CategoryAlreadyExistsException::class => Response::HTTP_CONFLICT,
    ];

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Messenger wraps handler exceptions in HandlerFailedException
        if ($exception instanceof HandlerFailedException) {
            $nested = $exception->getWrappedExceptions();
            $exception = reset($nested) ?: $exception;
        }

        // 1. Check explicit map
        $exceptionClass = $exception::class;
        if (isset(self::EXCEPTION_MAP[$exceptionClass])) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                self::EXCEPTION_MAP[$exceptionClass],
            ));
            return;
        }

        // 2. Convention-based: DomainException subclasses
        if ($exception instanceof \DomainException) {
            $statusCode = $this->resolveStatusCodeByConvention($exceptionClass);
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                $statusCode,
            ));
            return;
        }

        // 3. InvalidArgumentException → 400
        if ($exception instanceof \InvalidArgumentException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_BAD_REQUEST,
            ));
        }
    }

    /**
     * Resolve HTTP status code from exception class name convention:
     * - *NotFoundException → 404
     * - *AlreadyExistsException → 409
     * - *ForbiddenException → 403
     * - Default DomainException → 400
     */
    private function resolveStatusCodeByConvention(string $className): int
    {
        $shortName = substr(strrchr($className, '\\') ?: $className, 1);

        return match (true) {
            str_contains($shortName, 'NotFound') => Response::HTTP_NOT_FOUND,
            str_contains($shortName, 'AlreadyExists') => Response::HTTP_CONFLICT,
            str_contains($shortName, 'Forbidden') => Response::HTTP_FORBIDDEN,
            str_contains($shortName, 'Unauthorized') => Response::HTTP_UNAUTHORIZED,
            default => Response::HTTP_BAD_REQUEST,
        };
    }
}
