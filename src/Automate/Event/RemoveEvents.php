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

final class RemoveEvents
{
    // distinct from the DeployEvents names: both sets share the same dispatcher
    public const string INIT = 'remove.init';

    public const string FAILED = 'remove.failed';

    public const string TERMINATE = 'remove.terminate';
}
