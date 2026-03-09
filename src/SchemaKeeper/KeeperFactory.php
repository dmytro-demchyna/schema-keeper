<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper;

use PDO;
use SchemaKeeper\Comparator\{DumpComparator, SectionComparator};
use SchemaKeeper\Collector\{Dumper, SchemaItemFilter};
use SchemaKeeper\Provider\{IProvider, ProviderFactory};
use SchemaKeeper\Filesystem\{DumpReader, DumpWriter, FilesystemHelper, SectionReader, SectionWriter};
use SchemaKeeper\Dto\{Credentials, Parameters};

final class KeeperFactory
{
    public function createFromCredentials(Credentials $credentials, Parameters $parameters): Keeper
    {
        $conn = new PDO(
            $credentials->getDsn(),
            $credentials->getUser(),
            $credentials->getPassword(),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        return $this->create($conn, $parameters);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function createWithDefaults(
        PDO $conn,
        array $extraSkipSchemas = [],
        array $extraSkipExtensions = [],
        array $skippedSections = []
    ): Keeper {
        $params = new Parameters(
            array_values(array_unique(array_merge(
                Parameters::DEFAULT_SKIPPED_SCHEMAS,
                $extraSkipSchemas,
            ))),
            array_values(array_unique(array_merge(
                Parameters::DEFAULT_SKIPPED_EXTENSIONS,
                $extraSkipExtensions,
            ))),
            $skippedSections,
        );

        return $this->create($conn, $params);
    }

    public function create(PDO $conn, Parameters $parameters): Keeper
    {
        $providerFactory = new ProviderFactory();
        $provider = $providerFactory->createProvider($conn, $parameters);

        return $this->createFromProvider($provider, $parameters->getSkippedSections());
    }

    private function createFromProvider(IProvider $provider, array $skippedSections = []): Keeper
    {
        $schemaFilter = new SchemaItemFilter();
        $dumper = new Dumper($provider, $schemaFilter, $skippedSections);

        $helper = new FilesystemHelper();

        $sectionWriter = new SectionWriter($helper);
        $dumpWriter = new DumpWriter($sectionWriter, $helper);

        $sectionReader = new SectionReader($helper);
        $dumpReader = new DumpReader($sectionReader, $helper, $skippedSections);

        $sectionComparator = new SectionComparator();
        $comparator = new DumpComparator($sectionComparator);

        return new Keeper($dumper, $dumpWriter, $dumpReader, $comparator);
    }
}
