<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Fixture\Provider;

use Psr\Container\ContainerInterface;
use RuntimeException;
use SuperKernel\Context\Tests\Fixture\Container;

final class DefaultContainerProvider
{
    public static bool $fail = false;

    public static int $invocations = 0;

    public function __invoke(): ContainerInterface
    {
        ++self::$invocations;

        if (self::$fail) {
            throw new RuntimeException('Default container initialization failed.');
        }

        return new Container();
    }
}
