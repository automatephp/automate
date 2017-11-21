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

use Automate\Model\Platform;
use Symfony\Component\EventDispatcher\Event;

class SuccessDeployEvent extends Event
{
    private $platform;

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
