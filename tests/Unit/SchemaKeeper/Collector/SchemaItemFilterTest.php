<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Collector;

use SchemaKeeper\Collector\SchemaItemFilter;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class SchemaItemFilterTest extends UnitTestCase
{
    private SchemaItemFilter $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new SchemaItemFilter();
    }

    public function testOk(): void
    {
        $actual = $this->target->filter('schema1', [
            'schema1.table_name1' => 'table_content1',
            'schema2.table_name2' => 'table_content2',
        ]);

        $expected = [
            'table_name1' => 'table_content1',
        ];

        self::assertEquals($expected, $actual);
    }
}
