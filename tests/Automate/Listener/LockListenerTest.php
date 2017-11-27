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
use Automate\Session;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;

class LockListenerTest extends AbstractContextTest
{

    public function testInitLockFile()
    {
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        $logger = Phake::mock(ConsoleLogger::class);
        $session = new Session($ssh);
        $context = $this->createContext($session, $logger);

        $event = new DeployEvent($context);
        $listener = new LockListener();
        $listener->initLockFile($event);

        Phake::verify($ssh, Phake::times(1))->exec('touch /home/wwwroot/automate/demo/automate.lock');
    }

    public function testRemoveLockFile()
    {
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        Phake::when($ssh)->exec('if test -f "/home/wwwroot/automate/demo/automate.lock"; then echo "Y";fi')->thenReturn('Y');

        $logger = Phake::mock(ConsoleLogger::class);
        $session = new Session($ssh);
        $context = $this->createContext($session, $logger);

        $event = new DeployEvent($context);
        $listener = new LockListener();

        $reflection = new \ReflectionClass($listener);
        $reflectionProperty = $reflection->getProperty('hasLock');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($listener, true);

        $listener->clearLockFile($event);

        Phake::verify($ssh, Phake::times(1))->exec('rm /home/wwwroot/automate/demo/automate.lock');
    }
}
