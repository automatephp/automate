<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Session;

interface SessionInterface
{
    /**
     * Execute e command.
     */
    public function run(string $command);

    /**
     * Creates a directory.
     *
     * @param string $path      The name of the new directory
     * @param bool   $recursive Whether to automatically create any required
     *                          parent directory
     */
    public function mkdir(string $path, bool $recursive = false);

    /**
     * Move a file or a directory.
     *
     * @param string $from The current name of the directory or file
     * @param string $to   The new name of the directory or file
     */
    public function mv(string $from, string $to);

    /**
     * Removes a directory or a file.
     *
     * @param string $path The directory or file that is being removed
     */
    public function rm(string $path, bool $recursive = false);

    /**
     * Indicates whether the specified distant file or directory exists.
     *
     * @param string $path The distant filename ou directory
     */
    public function exists(string $path): bool;

    /**
     * Creates a symlink.
     *
     * @param string $target The target of the symlink
     * @param string $link   The path of the link
     */
    public function symlink(string $target, string $link);

    /**
     * Touch file.
     *
     * @param string $path FIle path
     */
    public function touch(string $path);

    /**
     * Lists directories of the specified path.
     */
    public function listDirectory(string $path): array;
}
