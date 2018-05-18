<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Event;

final class DeployEvents
{
    const INIT  = 'init';
    const BUILD = 'build';
    const DEPLOY = 'deploy';
    const FINISH = 'finish';
    const FAILED  = 'failed';
    const TERMINATE = 'terminate';
}
