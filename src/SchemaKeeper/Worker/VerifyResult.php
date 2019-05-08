<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Worker;

/**
 * If getExpected() != getActual() - the current database structure is different from the saved one.
 */
class VerifyResult
{
    /**
     * @var array
     */
    private $expected = [];

    /**
     * @var array
     */
    private $actual = [];

    /**
     * @param array $expected
     * @param array $actual
     */
    public function __construct(array $expected, array $actual)
    {
        $this->expected = $expected;
        $this->actual = $actual;
    }

    /**
     * @return array
     */
    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @return array
     */
    public function getActual()
    {
        return $this->actual;
    }
}
