<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Exception;


class DiffException extends KeeperException
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
     * @return array
     */
    public function getExpected()
    {
        return $this->expected;
    }

    /**
     * @param array $expected
     */
    public function setExpected(array $expected)
    {
        $this->expected = $expected;
    }

    /**
     * @return array
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * @param array $actual
     */
    public function setActual(array $actual)
    {
        $this->actual = $actual;
    }


}
