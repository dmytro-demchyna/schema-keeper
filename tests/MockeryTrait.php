<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests;

use Mockery;

trait MockeryTrait
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->addToAssertionCount(
            Mockery::getContainer()->mockery_getExpectationCount(),
        );

        Mockery::close();
    }
}
