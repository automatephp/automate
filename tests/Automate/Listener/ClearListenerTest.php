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
use Automate\Logger\ConsoleLogger;
use Automate\Session;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;

class ClearListenerTest extends AbstractContextTest
{

    public function testClearReleases()
    {
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);
        Phake::when($ssh)->exec('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->thenReturn('
            2016.08.30-0032.620
            ab
            2016.08.28-0032.620
            999
            2016.08.20-0032.620
            2016.08.27-0032.620
            test
            2016.08.29-0032.620
            failed
        ');

        $logger = Phake::mock(ConsoleLogger::class);
        $session = new Session($ssh);
        $context = $this->createContext($session, $logger);

        $event = new DeployEvent($context);
        $listener = new ClearListener();
        $listener->clearReleases($event);

        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.20-0032.620');
        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.27-0032.620');

        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.28-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.29-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.30-0032.620');

        Phake::verify($ssh, Phake::times(0))->exec('rm -R failed');
        Phake::verify($ssh, Phake::never(0))->exec('rm -R ab');
        Phake::verify($ssh, Phake::never(0))->exec('rm -R 999');
        Phake::verify($ssh, Phake::never(0))->exec('rm -R test');
    }

    public function testRemoveFailedRelease()
    {
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);
        $logger = Phake::mock(ConsoleLogger::class);
        $session = new Session($ssh);
        $context = $this->createContext($session, $logger);

        Phake::when($ssh)->exec('if test -f "/home/wwwroot/automate/demo/releases/failed"; then echo "Y";fi')->thenReturn('Y');

        $event = new DeployEvent($context);
        $listener = new ClearListener();
        $listener->removeFailedRelease($event);

        Phake::verify($ssh, Phake::times(1))->exec('rm -R /home/wwwroot/automate/demo/releases/failed');
    }

    public function testMoveFailedRelease()
    {
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);
        $logger = Phake::mock(ConsoleLogger::class);
        $session = new Session($ssh);
        $context = $this->createContext($session, $logger);

        $event = new FailedDeployEvent($context, new \Exception());
        $listener = new ClearListener();
        $listener->moveFailedRelease($event);

        Phake::verify($ssh, Phake::times(1))->exec(sprintf('mv /home/wwwroot/automate/demo/releases/%s /home/wwwroot/automate/demo/releases/failed', $context->getReleaseId()));
    }

}
