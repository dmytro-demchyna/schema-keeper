<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Dto;

final class Request
{
    private Credentials $credentials;

    private Parameters $params;

    private string $command;

    private string $path;

    public function __construct(Credentials $credentials, Parameters $params, string $command, string $path)
    {
        $this->credentials = $credentials;
        $this->params = $params;
        $this->command = $command;
        $this->path = $path;
    }

    public function getCredentials(): Credentials
    {
        return $this->credentials;
    }

    public function getParams(): Parameters
    {
        return $this->params;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
