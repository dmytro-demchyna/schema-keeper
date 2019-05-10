<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

class Result
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $status;

    /**
     * @var resource
     */
    private $outputStream;

    /**
     * @param string $message
     * @param int $status
     * @param resource $outputStream
     */
    public function __construct($message, $status, $outputStream)
    {
        $this->message = $message;
        $this->status = $status;
        $this->outputStream = $outputStream;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return resource
     */
    public function getOutputStream()
    {
        return $this->outputStream;
    }
}
