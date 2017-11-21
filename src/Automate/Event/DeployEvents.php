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
    const DEPLOY_START   = 'start';
    const DEPLOY_SUCCESS = 'success';
    const DEPLOY_FAILED  = 'failed';
}
