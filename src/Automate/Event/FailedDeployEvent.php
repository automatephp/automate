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

use Symfony\Component\EventDispatcher\Event;
use Automate\Model\Platform;

class FailedDeployEvent extends Event
{
    private $platform;
    private $exception;

    public function __construct(Platform $platform, \Exception $exception)
    {
        $this->platform = $platform;
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
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
