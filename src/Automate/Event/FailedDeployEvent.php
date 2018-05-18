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

use Automate\Context\ContextInterface;

class FailedDeployEvent extends DeployEvent
{
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(ContextInterface $context, \Exception $exception)
    {
        $this->exception = $exception;

        parent::__construct($context);
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

}
