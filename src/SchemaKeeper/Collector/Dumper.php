<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Collector;

use SchemaKeeper\Dto\{Dump, SchemaDump, Section};
use SchemaKeeper\Provider\IProvider;

final class Dumper
{
    private IProvider $provider;

    private SchemaItemFilter $filter;

    /**
     * @var string[]
     */
    private array $skippedSections;

    public function __construct(IProvider $provider, SchemaItemFilter $filter, array $skippedSections = [])
    {
        $this->provider = $provider;
        $this->filter = $filter;
        $this->skippedSections = $skippedSections;
    }

    public function dump(): Dump
    {
        $allData = [];

        foreach (Section::all() as $section) {
            if (in_array($section, $this->skippedSections, true)) {
                continue;
            }

            $allData[$section] = $this->provider->getData($section);
        }

        $extensions = $this->provider->getExtensions();

        $schemas = [];

        foreach ($this->provider->getSchemas() as $schemaName) {
            $sections = [];

            foreach (Section::all() as $section) {
                if (in_array($section, $this->skippedSections, true)) {
                    continue;
                }

                $sections[$section] = $this->filter->filter($schemaName, $allData[$section]);
            }

            $schemas[] = new SchemaDump($schemaName, $sections);
        }

        return new Dump($schemas, $extensions);
    }
}
