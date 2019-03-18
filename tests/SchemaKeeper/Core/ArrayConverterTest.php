<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Tests\Core;

use SchemaKeeper\Core\ArrayConverter;
use SchemaKeeper\Core\Dump;
use SchemaKeeper\Core\SchemaStructure;
use SchemaKeeper\Tests\SchemaTestCase;

class ArrayConverterTest extends SchemaTestCase
{
    /**
     * @var ArrayConverter
     */
    private $target;

    public function setUp()
    {
        parent::setUp();

        $this->target = new ArrayConverter();
    }

    public function testOk()
    {
        $extensions = ['ext1', 'ext2'];
        $tables = ['table' => 'table_content'];
        $views = ['view' => 'view_content'];
        $materializedViews = ['m_view' => 'm_view_content'];
        $types = ['type' => 'type_content'];
        $functions = ['function' => 'function_content'];
        $triggers = ['trigger' => 'trigger_content'];
        $sequences = ['sequence' => 'sequence_content'];

        $structure = new SchemaStructure('schema');
        $dump = new Dump([$structure], $extensions);
        $structure->setTables($tables);
        $structure->setViews($views);
        $structure->setMaterializedViews($materializedViews);
        $structure->setTypes($types);
        $structure->setFunctions($functions);
        $structure->setTriggers($triggers);
        $structure->setSequences($sequences);

        $expected = [
            'schemas' => [
                'schema',
            ],
            'extensions' => [
                'ext1',
                'ext2',
            ],
            'tables' => [
                'schema.table' => 'table_content',
            ],
            'views' => [
                'schema.view' => 'view_content',
            ],
            'materialized_views' => [
                'schema.m_view' => 'm_view_content',
            ],
            'types' => [
                'schema.type' => 'type_content',
            ],
            'functions' => [
                'schema.function' => 'function_content',
            ],
            'triggers' => [
                'schema.trigger' => 'trigger_content',
            ],
            'sequences' => [
                'schema.sequence' => 'sequence_content',
            ],
        ];

        $actual = $this->target->dump2Array($dump);

        self::assertEquals($expected, $actual);
    }
}
