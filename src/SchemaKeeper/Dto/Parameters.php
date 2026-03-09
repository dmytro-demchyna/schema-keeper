<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Dto;

final class Parameters
{
    public const DEFAULT_SKIPPED_SCHEMAS = [
        'information_schema',
        'pg_catalog',
        'pg_toast',
    ];

    public const DEFAULT_SKIPPED_EXTENSIONS = ['plpgsql'];

    /**
     * @var string[]
     */
    private array $skippedSchemas;

    /**
     * @var string[]
     */
    private array $skippedExtensions;

    /**
     * @var string[]
     */
    private array $skippedSections;

    private bool $skipTemporarySchemas;

    /**
     * @var string[]
     */
    private array $onlySchemas;

    public function __construct(
        array $skippedSchemas = [],
        array $skippedExtensions = [],
        array $skippedSections = [],
        bool $skipTemporarySchemas = true,
        array $onlySchemas = []
    ) {
        $this->skippedSchemas = $skippedSchemas;
        $this->skippedExtensions = $skippedExtensions;
        $this->skippedSections = $skippedSections;
        $this->skipTemporarySchemas = $skipTemporarySchemas;
        $this->onlySchemas = $onlySchemas;
    }

    public function getSkippedSchemas(): array
    {
        return $this->skippedSchemas;
    }

    public function getSkippedExtensions(): array
    {
        return $this->skippedExtensions;
    }

    public function getSkippedSections(): array
    {
        return $this->skippedSections;
    }

    public function shouldSkipTemporarySchemas(): bool
    {
        return $this->skipTemporarySchemas;
    }

    public function getOnlySchemas(): array
    {
        return $this->onlySchemas;
    }
}
