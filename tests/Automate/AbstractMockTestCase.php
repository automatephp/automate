<?php

namespace Automate\Tests;

use PHPUnit\Framework\TestCase;

class AbstractMockTestCase extends TestCase
{
    protected function tearDown(): void
    {
        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        \Mockery::close();
    }
}
