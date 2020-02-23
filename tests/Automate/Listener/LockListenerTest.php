<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Listener;

use Automate\Event\DeployEvent;
use Automate\Event\FailedDeployEvent;
use Automate\Listener\ClearListener;
use Automate\Listener\LockListener;
use Automate\Logger\ConsoleLogger;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;
use Prophecy\Argument;

class LockListenerTest extends AbstractContextTest
{

    public function testInitLockFile()
    {
        $ssh = $this->prophesize(SSH2::class);
        $ssh->getExitStatus()->willReturn(0);
        $ssh->setTimeout(0)->shouldBeCalled();

        $logger = $this->prophesize(ConsoleLogger::class);
        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal());

        $event = new DeployEvent($context);
        $listener = new LockListener();
        $listener->initLockFile($event);

        $ssh->exec(Argument::any())->shouldBeCalled();
        $ssh->exec('touch /home/wwwroot/automate/demo/automate.lock')->shouldBeCalled();
    }

    public function testRemoveLockFile()
    {
        $ssh = $this->prophesize(SSH2::class);
        $ssh->getExitStatus()->willReturn(0);
        $ssh->setTimeout(0)->shouldBeCalled();

        $logger = $this->prophesize(ConsoleLogger::class);
        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal());

        $event = new DeployEvent($context);
        $listener = new LockListener();

        $reflection = new \ReflectionClass($listener);
        $reflectionProperty = $reflection->getProperty('hasLock');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($listener, true);

        $listener->clearLockFile($event);

        $ssh->exec('rm /home/wwwroot/automate/demo/automate.lock')->shouldBeCalled();
    }
}
