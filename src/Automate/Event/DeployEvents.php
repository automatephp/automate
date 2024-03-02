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
    public const INIT = 'init';

    public const BUILD = 'build';

    public const DEPLOY = 'deploy';

    public const FINISH = 'finish';

    public const FAILED = 'failed';

    public const TERMINATE = 'terminate';
}
