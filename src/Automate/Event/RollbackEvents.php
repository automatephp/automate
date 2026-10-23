<?php

declare(strict_types=1);

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Event;

final class RollbackEvents
{
    // distinct from the other event names: they all share the same dispatcher
    public const string INIT = 'rollback.init';

    public const string FAILED = 'rollback.failed';

    public const string TERMINATE = 'rollback.terminate';
}
