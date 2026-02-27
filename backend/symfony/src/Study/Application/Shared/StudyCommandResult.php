<?php

namespace App\Study\Application\Shared;

final readonly class StudyCommandResult
{
    private function __construct(
        public bool $success,
        public int $statusCode,
        public ?array $data = null,
        public ?string $error = null,
    ) {}

    public static function ok(array $data): self
    {
        return new self(true, 200, $data);
    }

    public static function created(array $data): self
    {
        return new self(true, 201, $data);
    }

    public static function notFound(): self
    {
        return new self(false, 404, null, 'Not found');
    }

    public static function badRequest(string $message): self
    {
        return new self(false, 400, null, $message);
    }

    public static function deleted(): self
    {
        return new self(true, 204);
    }
}
