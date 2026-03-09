<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Integration;

use SchemaKeeper\Dto\Parameters;
use SchemaKeeper\Tests\PostgreSqlSetUpTrait;

abstract class PostgreSqlTestCase extends IntegrationTestCase
{
    use PostgreSqlSetUpTrait;

    protected static function getDbParams(): Parameters
    {
        return new Parameters(
            array_merge(Parameters::DEFAULT_SKIPPED_SCHEMAS, ['extensions']),
            Parameters::DEFAULT_SKIPPED_EXTENSIONS,
        );
    }
}
