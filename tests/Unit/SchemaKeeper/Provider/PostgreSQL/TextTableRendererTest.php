<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Unit\SchemaKeeper\Provider\PostgreSQL;

use SchemaKeeper\Provider\PostgreSQL\TextTableRenderer;
use SchemaKeeper\Tests\Unit\UnitTestCase;

class TextTableRendererTest extends UnitTestCase
{
    private TextTableRenderer $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = new TextTableRenderer();
    }

    public function testRender(): void
    {
        $title = 'Table "public.test"';
        $headers = [' Column', 'Type', 'Nullable'];
        $rows = [
            ['id', 'bigint', 'not null'],
            ['name', 'character varying(255)', ''],
        ];

        $actual = $this->target->render($title, $headers, $rows);

        $expected = ''
            . "            Table \"public.test\"\n"
            . " Column | Type                   | Nullable\n"
            . "--------+------------------------+---------\n"
            . " id     | bigint                 | not null\n"
            . " name   | character varying(255) |         \n";

        self::assertEquals($expected, $actual);
    }
}
