<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider;

interface IProvider
{
    /**
     * @return array<string, string>
     */
    public function getTables(): array;

    /**
     * @return array<string, string>
     */
    public function getViews(): array;

    /**
     * @return array<string, string>
     */
    public function getMaterializedViews(): array;

    /**
     * @return array<string, string>
     */
    public function getTriggers(): array;

    /**
     * @return array<string, string>
     */
    public function getFunctions(): array;

    /**
     * @return array<string, string>
     */
    public function getTypes(): array;

    /**
     * @return array<string, string>
     */
    public function getSchemas(): array;

    /**
     * @return array<string, string>
     */
    public function getExtensions(): array;

    /**
     * @return array<string, string>
     */
    public function getSequences(): array;

    /**
     * @param string $definition
     */
    public function createFunction(string $definition): void;

    /**
     * @param string $name
     */
    public function deleteFunction(string $name): void;

    /**
     * @param string $name
     * @param string $definition
     */
    public function changeFunction(string $name, string $definition): void;
}
