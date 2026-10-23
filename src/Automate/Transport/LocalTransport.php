<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Transport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class LocalTransport implements TransportInterface
{
    public function login(): void
    {
    }

    public function exec(string $command): string
    {
        $process = $this->createProcess($command);

        // stdout and stderr in a single stream, as the SSH transport returns them
        $output = '';
        $process->run(static function (string $type, string $buffer) use (&$output): void {
            $output .= $buffer;
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($output);
        }

        return $output;
    }

    public function upload(string $path, string $target): void
    {
        new Filesystem()->copy($path, $target, true);
    }

    public function execAsync(string $command): Process
    {
        return $this->createProcess($command);
    }

    private function createProcess(string $command): Process
    {
        return Process::fromShellCommandline($command, timeout: null);
    }
}
