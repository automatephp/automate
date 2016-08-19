<?php


namespace Automate;


class Session
{
    public function __construct()
    {

    }


    /**
     * Execute e command
     *
     * @param  string  $command
     *
     * @return mixed
     */
    public function exec($command)
    {

    }

    /**
     * Creates a directory
     *
     * @param  string  $path      The name of the new directory
     * @param  boolean $recursive Whether to automatically create any required
     *                            parent directory
     *
     * @return boolean TRUE on success, or FALSE on failure
     */
    public function mkdir($path, $recursive = false)
    {

    }

    /**
     * Move a file or a directory
     *
     * @param  string  $from    The current name of the directory or file
     * @param  string  $to      The new name of the directory or file
     *
     * @return boolean TRUE on success, or FALSE on failure
     */
    public function mv($from, $to)
    {

    }

    /**
     * Removes a directory or a file
     *
     * @param  string  $path The directory or file that is being removed
     * @param  boolean $recursive
     *
     * @return boolean TRUE on success, or FALSE on failure
     */
    public function rm($path, $recursive = false)
    {

    }

    /**
     * Indicates whether the specified distant file or directory exists
     *
     * @param  string $path The distant filename ou directory
     *
     * @return boolean
     */
    public function exists($path)
    {

    }

    /**
     * Creates a symlink
     *
     * @param  string $target The target of the symlink
     * @param  string $link   The path of the link
     *
     * @return boolean TRUE on success, or FALSE on failure
     */
    public function symlink($target, $link)
    {

    }

    /**
     * Lists files and directories of the specified path
     *
     * @param  string $path
     *
     * @return array
     */
    public function ls($path)
    {

    }

}