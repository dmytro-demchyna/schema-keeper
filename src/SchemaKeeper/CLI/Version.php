<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

class Version
{
    const VERSION = 'v2.2.0';

    /**
     * @return string
     */
    public static function getVersionText()
    {
        return 'SchemaKeeper ' . self::VERSION . ' by Dmytro Demchyna and contributors.' . PHP_EOL. PHP_EOL;
    }
}
