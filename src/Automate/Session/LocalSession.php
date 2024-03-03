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

use Symfony\Component\Process\Process;

class LocalSession extends AbstractSession
{
    public function run($command)
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getOutput());
        }

        return $process->getOutput();
    }
}
