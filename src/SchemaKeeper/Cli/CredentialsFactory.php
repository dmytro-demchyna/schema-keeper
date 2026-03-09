<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Cli;

use SchemaKeeper\Dto\Credentials;
use SchemaKeeper\Exception\KeeperException;

final class CredentialsFactory
{
    private const CONNECTION_OPTIONS = ['host', 'port', 'dbname', 'username', 'password'];

    public function createFromOptions(array $options): Credentials
    {
        $hasUrl = isset($options['url']);

        $hasConnectionOption = false;

        foreach (self::CONNECTION_OPTIONS as $opt) {
            if (isset($options[$opt])) {
                $hasConnectionOption = true;

                break;
            }
        }

        if ($hasUrl && $hasConnectionOption) {
            throw new KeeperException(
                'Cannot combine --url with individual connection options (-h, -p, -d, -U, --password)',
            );
        }

        if ($hasUrl) {
            $url = $this->getSingleOption($options, 'url');

            if ($url === null || $url === '') {
                throw new KeeperException('Option --url requires a value');
            }

            return $this->createFromUrl($url);
        }

        if ($hasConnectionOption) {
            $host = $this->getSingleOption($options, 'host');
            $port = $this->getSingleOption($options, 'port');
            $dbname = $this->getSingleOption($options, 'dbname');
            $username = $this->getSingleOption($options, 'username');
            $password = $this->getSingleOption($options, 'password');

            if ($host === null) {
                throw new KeeperException('Required option -h (--host) not specified');
            }

            if ($port === null) {
                throw new KeeperException('Required option -p (--port) not specified');
            }

            if ($dbname === null) {
                throw new KeeperException('Required option -d (--dbname) not specified');
            }

            if ($username === null) {
                throw new KeeperException('Required option -U (--username) not specified');
            }

            return $this->createFromParams($host, $port, $dbname, $username, $password);
        }

        throw new KeeperException(
            'No connection parameters specified. Use -h, -p, -d, -U or --url',
        );
    }

    public function createFromParams(
        string $host,
        string $port,
        string $dbname,
        string $user,
        ?string $password
    ): Credentials {
        $dsn = 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname;

        return new Credentials($dsn, $user, $password);
    }

    public function createFromUrl(string $url): Credentials
    {
        $parts = parse_url($url);

        if ($parts === false) {
            throw new KeeperException('Invalid connection URL');
        }

        $scheme = $parts['scheme'] ?? '';

        if ($scheme !== 'postgresql' && $scheme !== 'postgres') {
            throw new KeeperException(
                'Invalid URL scheme: "' . $scheme . '". Expected "postgresql" or "postgres"',
            );
        }

        $host = isset($parts['host']) ? urldecode($parts['host']) : null;

        if ($host === null || $host === '') {
            throw new KeeperException('Connection URL is missing host');
        }

        $port = isset($parts['port']) ? (string) $parts['port'] : null;

        if ($port === null) {
            throw new KeeperException('Connection URL is missing port');
        }

        $path = $parts['path'] ?? '';
        $dbname = urldecode(ltrim($path, '/'));

        if ($dbname === '') {
            throw new KeeperException('Connection URL is missing database name');
        }

        $user = isset($parts['user']) ? urldecode($parts['user']) : null;

        if ($user === null || $user === '') {
            throw new KeeperException('Connection URL is missing username');
        }

        $password = isset($parts['pass']) ? urldecode($parts['pass']) : null;

        return $this->createFromParams($host, $port, $dbname, $user, $password);
    }

    private function getSingleOption(array $options, string $name): ?string
    {
        if (!isset($options[$name])) {
            return null;
        }

        $values = $options[$name];

        if (count($values) > 1) {
            throw new KeeperException('Option --' . $name . ' specified more than once');
        }

        return $values[0];
    }
}
