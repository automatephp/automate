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

class FailedDeployEvent extends DeployEvent
{
    public function __construct(Context $context, private readonly \Exception $exception)
    {
        parent::__construct($context);
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }
}
