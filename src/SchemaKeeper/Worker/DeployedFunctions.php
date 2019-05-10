<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Worker;

class DeployedFunctions
{
    /**
     * @var array
     */
    private $changed = [];

    /**
     * @var array
     */
    private $created = [];

    /**
     * @var array
     */
    private $deleted = [];

    /**
     * @param array $changed
     * @param array $created
     * @param array $deleted
     */
    public function __construct(array $changed, array $created, array $deleted)
    {
        $this->changed = $changed;
        $this->created = $created;
        $this->deleted = $deleted;
    }

    /**
     * List of functions that were changed in the current database, as their source code is different between saved dump and current database
     * @return array
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * List of functions that were created in the current database, as they do not exist in the saved dump
     * @return array
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * List of functions that were deleted from the current database, as they do not exist in the saved dump
     * @return array
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
}
