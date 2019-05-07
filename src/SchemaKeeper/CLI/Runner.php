<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

use SchemaKeeper\Keeper;

class Runner
{
    /**
     * @var Keeper
     */
    private $keeper;

    /**
     * @param Keeper $keeper
     */
    public function __construct(Keeper $keeper)
    {
        $this->keeper = $keeper;
    }

    /**
     * @param string $command
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function run($command, $path)
    {
        $message = 'Undefined';

        switch ($command) {
            case 'save':
                $this->keeper->saveDump($path);
                $message = 'Dump saved to '.$path;

                break;
            case 'verify':
                $result = $this->keeper->verifyDump($path);

                if ($result['expected'] !== $result['actual']) {
                    throw new \Exception("Diff ");
                }

                $message = 'Dump successfully verified '.$path;

                break;
            case 'deploy':
                $result = $this->keeper->deployDump($path);

                if ($result['expected'] !== $result['actual']) {
                    throw new \Exception("Deploy ");
                }

                $message = 'Dump successfully deployed '.$path;

                break;
            default:
                throw new \Exception("Command ".$command.' not existed');

                break;
        }

        return $message;
    }
}
