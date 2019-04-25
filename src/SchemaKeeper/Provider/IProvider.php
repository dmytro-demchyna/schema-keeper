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
     * @return array
     */
    public function getTables();

    /**
     * @return array
     */
    public function getViews();

    /**
     * @return array
     */
    public function getMaterializedViews();

    /**
     * @return array
     */
    public function getTriggers();

    /**
     * @return array
     */
    public function getFunctions();

    /**
     * @return array
     */
    public function getTypes();

    /**
     * @return array
     */
    public function getSchemas();

    /**
     * @return array
     */
    public function getExtensions();

    /**
     * @return array
     */
    public function getSequences();

    /**
     * @param string $definition
     */
    public function createFunction($definition);

    /**
     * @param string $name
     */
    public function deleteFunction($name);

    /**
     * @param string $name
     * @param string $definition
     */
    public function changeFunction($name, $definition);
}
