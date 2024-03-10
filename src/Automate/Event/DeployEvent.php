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

use Automate\Workflow\Context;
use Symfony\Contracts\EventDispatcher\Event;

class DeployEvent extends Event
{
    public function __construct(
        private readonly Context $context,
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
