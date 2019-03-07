<?php

namespace SchemaKeeper\Filesystem;

use Exception;

class FilesystemHelper
{
    /**
     * @param string $path
     * @return bool
     */
    public function isDir($path)
    {
        return is_dir($path);
    }

    /**
     * @param string $filename
     * @return string
     * @throws Exception
     */
    public function fileGetContents($filename)
    {
        $content = file_get_contents($filename);

        if ($content === false) {
            throw new Exception('file_get_contents error on: '.$filename);
        }

        return $content;
    }

    /**
     * @param string $filename
     * @param string $data
     * @throws Exception
     */
    public function filePutContents($filename, $data)
    {
        $result = file_put_contents($filename, $data);

        if ($result === false) {
            throw new Exception('file_put_contents error on: '.$filename);
        }
    }

    /**
     * @param string $pattern
     * @return array|false
     */
    public function glob($pattern)
    {
        return glob($pattern);
    }

    /**
     * @param string $pathname
     * @param int $mode
     * @param bool $recursive
     * @throws Exception
     */
    public function mkdir($pathname, $mode = 0775, $recursive = false)
    {
        $result = mkdir($pathname, $mode, $recursive);

        if ($result === false) {
            throw new Exception('mkdir error on: '.$pathname);
        }
    }

    /**
     * @param string $path
     */
    public function rmDirIfExisted($path)
    {
        if ($this->isDir($path)) {
            shell_exec("rm -rf ".escapeshellarg($path));
        }
    }
}
