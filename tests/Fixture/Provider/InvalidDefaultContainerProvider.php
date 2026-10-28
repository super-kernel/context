<?php

declare(strict_types=1);

namespace SuperKernel\Context\Tests\Fixture\Provider;

use stdClass;

final class InvalidDefaultContainerProvider
{
    public function __invoke(): object
    {
        return new stdClass();
    }
}
