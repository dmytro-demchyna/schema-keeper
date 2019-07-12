<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Outside;

/**
 * @api
 */
class DeployedFunctions
{
    /**
     * @var string[]
     */
    private $changed = [];

    /**
     * @var string[]
     */
    private $created = [];

    /**
     * @var string[]
     */
    private $deleted = [];

    /**
     * @param string[] $changed
     * @param string[] $created
     * @param string[] $deleted
     */
    public function __construct(array $changed, array $created, array $deleted)
    {
        $this->changed = $changed;
        $this->created = $created;
        $this->deleted = $deleted;
    }

    /**
     * List of functions that were changed in the current database, as their source code is different between saved dump and current database
     * @return string[]
     */
    public function getChanged(): array
    {
        return $this->changed;
    }

    /**
     * List of functions that were created in the current database, as they do not exist in the saved dump
     * @return string[]
     */
    public function getCreated(): array
    {
        return $this->created;
    }

    /**
     * List of functions that were deleted from the current database, as they do not exist in the saved dump
     * @return string[]
     */
    public function getDeleted(): array
    {
        return $this->deleted;
    }
}
