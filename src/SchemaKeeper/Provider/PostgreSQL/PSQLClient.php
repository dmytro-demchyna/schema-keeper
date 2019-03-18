<?php
/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmitry.demchina@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SchemaKeeper\Provider\PostgreSQL;

class PSQLClient
{
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
     * @param string $dbName
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     */
    public function __construct($dbName, $host, $port, $user, $password)
    {
        $this->dbName = $dbName;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param string $command
     * @return string|null
     */
    public function run($command)
    {
        putenv("PGPASSWORD=" . $this->password);

        $req = "echo " . escapeshellarg($command) . " | psql -U" . $this->user . " -h" . $this->host . " -p" . $this->port . " -d" . $this->dbName;

        return shell_exec($req);
    }

    /**
     * @param array $commands
     * @return array
     */
    public function runMultiple(array $commands)
    {
        putenv("PGPASSWORD=" . $this->password);

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

        $req = "psql -U" . $this->user . " -h" . $this->host . " -p" . $this->port . " -d" . $this->dbName . $commandsString;

        $rawOutput = shell_exec($req);

        $outputs = explode($separator, $rawOutput);

        $i = 0;
        foreach ($commands as $table => $cmd) {
            $results[$table] = $outputs[$i];
            $i++;
        }

        return $results;
    }
}
