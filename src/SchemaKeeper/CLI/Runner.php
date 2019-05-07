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
        $message = '';

        switch ($command) {
            case 'save':
                $this->keeper->saveDump($path);
                $message = 'Dump saved to '.$path;

                break;
            case 'verify':
                $result = $this->keeper->verifyDump($path);

                if ($result['expected'] !== $result['actual']) {
                    throw new KeeperException("Dump and current database not equals: ".json_encode($result));
                }

                $message = 'Dump verified '.$path;

                break;
            case 'deploy':
                $result = $this->keeper->deployDump($path);

                $message = 'Dump deployed '.$path.".\n";

                foreach ($result['deleted'] as $nameDeleted) {
                    $message .= "Deleted $nameDeleted\n";
                }

                foreach ($result['created'] as $nameCreated) {
                    $message .= "Created $nameCreated\n";
                }

                foreach ($result['changed'] as $nameChanged) {
                    $message .= "Changed $nameChanged\n";
                }

                break;
            default:
                throw new KeeperException("Command ".$command.' not exists');

                break;
        }

        return $message;
    }
}
