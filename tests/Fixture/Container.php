<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Fixture;

use Psr\Container\ContainerInterface;

use function array_key_exists;

final readonly class Container implements ContainerInterface
{
    /** @param array<string, mixed> $entries */
    public function __construct(private array $entries = [])
    {
    }

    public function get(string $id): mixed
    {
        return array_key_exists($id, $this->entries) ? $this->entries[$id] : $id;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries) || '' !== $id;
    }
}
