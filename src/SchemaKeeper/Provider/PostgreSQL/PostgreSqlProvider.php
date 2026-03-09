<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Provider\PostgreSQL;

use SchemaKeeper\Provider\{IDescriber, IProvider};

final class PostgreSqlProvider implements IProvider
{
    /**
     * @var array<string, IDescriber>
     */
    private array $describers;

    private SchemaDescriber $schemaDescriber;

    private ExtensionDescriber $extensionDescriber;

    /**
     * @param array<string, IDescriber> $describers
     */
    public function __construct(
        array $describers,
        SchemaDescriber $schemaDescriber,
        ExtensionDescriber $extensionDescriber
    ) {
        $this->describers = $describers;
        $this->schemaDescriber = $schemaDescriber;
        $this->extensionDescriber = $extensionDescriber;
    }

    public function getData(string $section): array
    {
        return isset($this->describers[$section])
            ? $this->describers[$section]->describeAll()
            : [];
    }

    public function getSchemas(): array
    {
        return $this->schemaDescriber->describeAll();
    }

    public function getExtensions(): array
    {
        return $this->extensionDescriber->describeAll();
    }
}
