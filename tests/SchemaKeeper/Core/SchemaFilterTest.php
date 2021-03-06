<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Core;

use SchemaKeeper\Core\SchemaFilter;
use SchemaKeeper\Tests\SchemaTestCase;

class SchemaFilterTest extends SchemaTestCase
{
    /**
     * @var SchemaFilter
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $this->target = new SchemaFilter();
    }

    public function testOk()
    {
        $actual = $this->target->filter('schema1', [
           'schema1.table_name1' => 'table_content1',
           'schema2.table_name2' => 'table_content2',
        ]);

        $expected = [
           'table_name1' => 'table_content1'
        ];

        self::assertEquals($expected, $actual);
    }
}
