<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Dto;

final class Result
{
    private string $message;

    private int $status;

    public function __construct(string $message, int $status)
    {
        $this->message = $message;
        $this->status = $status;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getMessage(): string
    {
        return $this->message;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getStatus(): int
    {
        return $this->status;
    }
}
