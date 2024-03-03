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
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class SessionFactory
{
    /**
     * Create session.
     *
     * @throws \Exception
     */
    public function create(Server $server): SessionInterface
    {
        $ssh = new SSH2($server->getHost(), $server->getPort());

        // Connection with ssh key and optional
        if (null !== $server->getSshKey()) {
            if (!file_exists($server->getSshKey())) {
                throw new \Exception(sprintf('[%s] File "'.$server->getSshKey().'" not found', $server->getName()));
            }

            $key = PublicKeyLoader::load(file_get_contents($server->getSshKey()), $server->getPassword());
            if (!$ssh->login($server->getUser(), $key)) {
                throw new \Exception(sprintf('[%s] SSH key or passphrase is invalid', $server->getName()));
            }
        } elseif (!$ssh->login($server->getUser(), $server->getPassword())) {
            throw new \Exception(sprintf('[%s] Invalid user or password', $server->getName()));
        }

        return new SSHSession($ssh);
    }
}
