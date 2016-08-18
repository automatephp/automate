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
use Ssh\Authentication\Password;
use Ssh\Configuration;
use Ssh\Session;

class SessionFactory
{

    /**
     * Create SSH session
     *
     * @param Server $server
     *
     * @return Session
     */
    public function create(Server $server)
    {
        $configuration = new Configuration($server->getHost());
        $authentication = new Password($server->getUser(), $server->getPassword());

        $session = new Session($configuration, $authentication);
        $session->getResource();

        return $session;
    }

}