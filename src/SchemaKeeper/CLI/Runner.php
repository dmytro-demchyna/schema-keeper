<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\CLI;

use SchemaKeeper\Exception\KeeperException;
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
        switch ($command) {
            case 'save':
                $this->keeper->saveDump($path);
                $message = 'Dump saved to ' . $path;

                break;
            case 'verify':
                $this->keeper->verifyDump($path);

                $message = 'Dump verified ' . $path;

                break;
            case 'deploy':
                $result = $this->keeper->deployDump($path);

                $message = '';

                foreach ($result->getDeleted() as $nameDeleted) {
                    $message .= "\nDeleted $nameDeleted";
                }

                foreach ($result->getCreated() as $nameCreated) {
                    $message .= "\nCreated $nameCreated";
                }

                foreach ($result->getChanged() as $nameChanged) {
                    $message .= "\nChanged $nameChanged";
                }

                if ($message) {
                    $message = 'Dump deployed ' . $path.$message;
                } else {
                    $message = 'Nothing to deploy '.$path;
                }

                break;
            default:
                throw new KeeperException("Command " . $command . ' not exists');

                break;
        }

        return $message;
    }
}
