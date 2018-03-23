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
use Automate\Session\LocalSession;
use Automate\Session\SessionInterface;

class LocalContext extends Context
{
    /**
     * connect servers.
     */
    public function connect()
    {
    }

    /**
     * @param Server $server
     *
     * @return SessionInterface
     */
    public function getSession(Server $server)
    {
        return new LocalSession();
    }

}
