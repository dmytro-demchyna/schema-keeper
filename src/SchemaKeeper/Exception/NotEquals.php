<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Exception;

class NotEquals extends KeeperException
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
     * @param string $message
     * @param array $expected
     * @param array $actual
     */
    public function __construct($message, array $expected, array $actual)
    {
        $message .= ' '.json_encode([
            'expected' => $expected,
            'actual' => $actual,
        ]);

        parent::__construct($message);

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