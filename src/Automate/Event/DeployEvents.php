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
    public const string INIT = 'init';

    public const string BUILD = 'build';

    public const string DEPLOY = 'deploy';

    public const string FINISH = 'finish';

    public const string FAILED = 'failed';

    public const string TERMINATE = 'terminate';
}
