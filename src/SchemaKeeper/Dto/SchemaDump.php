<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Dto;

final class SchemaDump
{
    private string $schemaName;

    private array $sections;

    public function __construct(string $schemaName, array $sections = [])
    {
        $defaults = array_fill_keys(Section::all(), []);
        $this->schemaName = $schemaName;
        $this->sections = array_merge($defaults, $sections);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getSchemaName(): string
    {
        return $this->schemaName;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getSection(string $section): array
    {
        return $this->sections[$section] ?? [];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getSections(): array
    {
        return $this->sections;
    }
}
