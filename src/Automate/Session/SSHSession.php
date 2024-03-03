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

use phpseclib3\Net\SSH2;

class SSHSession extends AbstractSession
{
    /**
     * Session constructor.
     */
    public function __construct(
        private readonly SSH2 $ssh,
    ) {
        $this->ssh->setTimeout(0);
    }

    public function run($command): string
    {
        $rs = (string) $this->ssh->exec($command);

        if (0 !== $this->ssh->getExitStatus()) {
            throw new \RuntimeException($rs);
        }

        return $rs;
    }
}
