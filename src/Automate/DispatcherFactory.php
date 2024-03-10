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

use Automate\Listener\ClearListener;
use Automate\Listener\LockListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

readonly class DispatcherFactory
{
    public static function create(): EventDispatcher
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addSubscriber(new LockListener());
        $dispatcher->addSubscriber(new ClearListener());

        return $dispatcher;
    }
}
