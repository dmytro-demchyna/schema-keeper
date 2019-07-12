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

    public function __construct(string $message, int $status)
    {
        $this->message = $message;
        $this->status = $status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
