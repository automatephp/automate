<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use Automate\Model\Server;
use Automate\Session\SessionInterface;
use Automate\Session\SSHSession;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

class SessionFactory
{
    /**
     * Create session.
     *
     * @throws \Exception
     *
     * @return SessionInterface
     */
    public function create(Server $server)
    {
        $ssh = new SSH2($server->getHost(), $server->getPort());

        // Connection with ssh key and optional
        if (!empty($server->getSshKey())) {
            if (!file_exists($server->getSshKey())) {
                throw new \Exception(sprintf('[%s] File "'.$server->getSshKey().'" not found', $server->getName()));
            }

            $key = new RSA();
            $key->setPassword($server->getPassword());
            $key->loadKey(file_get_contents($server->getSshKey()));
            if (!$ssh->login($server->getUser(), $key)) {
                throw new \Exception(sprintf('[%s] SSH key or passphrase is invalid', $server->getName()));
            }
        } elseif (!$ssh->login($server->getUser(), $server->getPassword())) {
            throw new \Exception(sprintf('[%s] Invalid user or password', $server->getName()));
        }

        return new SSHSession($ssh);
    }
}
