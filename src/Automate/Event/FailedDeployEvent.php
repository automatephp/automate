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

use Automate\Context;
use Symfony\Component\EventDispatcher\Event;

class FailedDeployEvent extends Event
{
    /**
     * @var Context
     */
    private $context;
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(Context $context, \Exception $exception)
    {
        $this->context = $context;
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }
}
