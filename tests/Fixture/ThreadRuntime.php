<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Fixture;

final class ThreadRuntime
{
    public static int $id = 0;

    public static function getId(): int
    {
        return self::$id;
    }
}
