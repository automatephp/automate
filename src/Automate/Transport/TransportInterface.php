<?php

declare(strict_types=1);

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Transport;

use Symfony\Component\Process\Process;

/**
 * How Automate reaches a server: runs commands and sends files on it.
 */
interface TransportInterface
{
    public function login(): void;

    /**
     * @throws \RuntimeException when the command fails, with its output as message
     *
     * @return string the standard and error outputs of the command
     */
    public function exec(string $command): string;

    public function upload(string $path, string $target): void;

    /**
     * @return Process a process to start, which runs the command on the server
     */
    public function execAsync(string $command): Process;
}
