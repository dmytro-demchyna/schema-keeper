<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Exception;

final class NotEquals extends KeeperException
{
    private array $expected = [];

    private array $actual = [];

    public function __construct(string $message, array $expected, array $actual)
    {
        parent::__construct($message);

        $this->expected = $expected;
        $this->actual = $actual;
    }

    public function getExpected(): array
    {
        return $this->expected;
    }

    public function getActual(): array
    {
        return $this->actual;
    }
}
