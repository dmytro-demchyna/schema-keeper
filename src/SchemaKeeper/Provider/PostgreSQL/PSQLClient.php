<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider\PostgreSQL;

use SchemaKeeper\Exception\KeeperException;

class PSQLClient
{
    /**
     * @var string
     */
    private $executable;

    /**
     * @var string
     */
    protected $dbName;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * @param string $executable
     * @param string $dbName
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     */
    public function __construct($executable, $dbName, $host, $port, $user, $password)
    {
        $this->executable = $executable;
        $this->dbName = $dbName;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param string $command
     * @return string|null
     * @throws KeeperException
     */
    public function run($command)
    {
        $this->putPassword();

        $req = "echo " . escapeshellarg($command) . " | ".$this->generateScript();

        return shell_exec($req);
    }

    /**
     * @param array $commands
     * @return array
     * @throws KeeperException
     */
    public function runMultiple(array $commands)
    {
        $this->putPassword();

        if (!$commands) {
            return [];
        }

        $results = [];

        if (count($commands) > 500) {
            $parts = array_chunk($commands, 500, true);

            foreach ($parts as $part) {
                $results = array_merge($results, $this->runMultiple($part));
            }

            return $results;
        }

        $commandsString = '';
        $separator = '##|$$1$$$$#$$1$$$|##';


        foreach ($commands as $cmd) {
            $commandsString .= ' -c ' . escapeshellarg($cmd) . ' -c ' . escapeshellarg("\qecho -n '" . $separator . "'");
        }

        $req = $this->generateScript() . $commandsString;

        $rawOutput = shell_exec($req);

        $outputs = explode($separator, $rawOutput);

        $i = 0;
        foreach ($commands as $table => $cmd) {
            $results[$table] = $outputs[$i];
            $i++;
        }

        return $results;
    }

    private function generateScript()
    {
        return $this->executable . ' -U' . $this->user . ' -h' . $this->host . ' -p' . $this->port . ' -d' . $this->dbName;
    }

    private function putPassword()
    {
        putenv("PGPASSWORD=" . $this->password);
    }
}
