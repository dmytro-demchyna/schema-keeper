<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Tests\Acceptance\SchemaKeeper\Cli;

use SchemaKeeper\Cli\EntryPoint;
use SchemaKeeper\Tests\Acceptance\AcceptanceTestCase;
use SchemaKeeper\Tests\PostgreSqlSetUpTrait;

class ShellEntryPointTest extends AcceptanceTestCase
{
    use PostgreSqlSetUpTrait;

    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/sk_shell_' . uniqid();
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->tmpDir));

        parent::tearDown();
    }

    public function testOk(): void
    {
        $connArgs = $this->getShellConnArgs();
        exec('/data/bin/schemakeeper ' . $connArgs . ' dump ' . escapeshellarg($this->tmpDir), $output, $status);
        $output = implode(PHP_EOL, $output);
        self::assertEquals($this->getExpectedOutput('Success: Dump saved ' . $this->tmpDir), $output);
        self::assertSame(0, $status);
    }

    public function testError(): void
    {
        $connArgs = $this->getShellConnArgs();
        exec('/data/bin/schemakeeper ' . $connArgs . ' verify ' . escapeshellarg($this->tmpDir), $output, $status);
        $output = implode(PHP_EOL, $output);
        self::assertEquals($this->getExpectedOutput('Failure: Dump is empty: ' . $this->tmpDir), $output);
        self::assertSame(3, $status);
    }

    public function testUnrecognizedOption(): void
    {
        $connArgs = $this->getShellConnArgs();
        exec('/data/bin/schemakeeper ' . $connArgs . ' --blabla dump ' . escapeshellarg($this->tmpDir), $output, $status);
        $output = implode(PHP_EOL, $output);
        self::assertEquals($this->getExpectedOutput('Failure: Unrecognized option: blabla'), $output);
        self::assertSame(3, $status);
    }

    private function getShellConnArgs(): string
    {
        $host = self::getDbHost();

        return '-h ' . $host . ' -p 5432 -d schema_keeper -U postgres --password postgres';
    }

    private function getExpectedOutput(string $message): string
    {
        return trim(EntryPoint::getVersionText()) . PHP_EOL . $message;
    }
}
