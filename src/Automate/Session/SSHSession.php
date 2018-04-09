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

use phpseclib\Net\SSH2;

class SSHSession extends AbstractSession
{
    /**
     * @var SSH2
     */
    private $ssh;

    /**
     * Session constructor.
     *
     * @param SSH2 $ssh
     */
    public function __construct(SSH2 $ssh)
    {
        $this->ssh = $ssh;
        $this->ssh->setTimeout(0);
    }

    /**
     * {@inheritdoc}
     */
    public function run($command)
    {
        $rs = $this->ssh->exec($command);

        if (0 !== $this->ssh->getExitStatus()) {
            throw new \RuntimeException($rs);
        }

        return $rs;
    }
}
