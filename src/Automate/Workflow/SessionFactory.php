<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Workflow;

use Automate\Model\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class SessionFactory
{
    public function create(Server $server, string $releaseId): Session
    {
        $sftp = new SFTP($server->getHost(), $server->getPort());

        // Connection with ssh key and optional
        if (null !== $server->getSshKey()) {
            if (!file_exists($server->getSshKey())) {
                throw new \Exception(sprintf('[%s] File "'.$server->getSshKey().'" not found', $server->getName()));
            }

            $key = PublicKeyLoader::load(file_get_contents($server->getSshKey()), $server->getPassword());
            if (!$sftp->login($server->getUser(), $key)) {
                throw new \Exception(sprintf('[%s] SSH key or passphrase is invalid', $server->getName()));
            }
        } elseif (!$sftp->login($server->getUser(), $server->getPassword())) {
            throw new \Exception(sprintf('[%s] Invalid user or password', $server->getName()));
        }

        return new Session($server, $sftp, $releaseId);
    }
}
