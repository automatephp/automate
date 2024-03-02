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
use Automate\Listener\LockListener;
use Automate\Logger\ConsoleLogger;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use phpseclib\Net\SSH2;

class LockListenerTest extends AbstractContextTest
{
    public function testInitLockFile()
    {
        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);

        $logger = \Mockery::spy(ConsoleLogger::class);
        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $ssh->expects('exec')->with('touch /home/wwwroot/automate/demo/automate.lock')->once();

        $event = new DeployEvent($context);
        $listener = new LockListener();
        $listener->initLockFile($event);
    }

    public function testRemoveLockFile()
    {
        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);

        $logger = \Mockery::spy(ConsoleLogger::class);
        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $event = new DeployEvent($context);
        $listener = new LockListener();

        $ssh->expects('exec')->with('rm /home/wwwroot/automate/demo/automate.lock')->once();

        $reflection = new \ReflectionClass($listener);
        $reflectionProperty = $reflection->getProperty('hasLock');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($listener, true);

        $listener->clearLockFile($event);
    }
}
