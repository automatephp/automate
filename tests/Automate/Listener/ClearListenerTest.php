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
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTest;
use Phake;
use phpseclib\Net\SSH2;
use Prophecy\Argument;

class ClearListenerTest extends AbstractContextTest
{

    public function testClearReleases()
    {
        $ssh = $this->prophesize(SSH2::class);
        $ssh->getExitStatus()->willReturn(0);
        $ssh->setTimeout(0)->shouldBeCalled();
        $ssh->exec('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->willReturn('
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

        $logger = $this->prophesize(ConsoleLogger::class);
        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal());

        $event = new DeployEvent($context);
        $listener = new ClearListener();
        $listener->clearReleases($event);

        $ssh->exec('rm -R 2016.08.20-0032.620')->shouldBeCalled();
        $ssh->exec('rm -R 2016.08.27-0032.620')->shouldBeCalled();

        $ssh->exec('rm -R 2016.08.28-0032.620')->shouldNotBeCalled();
        $ssh->exec('rm -R 2016.08.29-0032.620')->shouldNotBeCalled();
        $ssh->exec('rm -R 2016.08.30-0032.620')->shouldNotBeCalled();
        $ssh->exec('rm -R failed')->shouldNotBeCalled();
        $ssh->exec('rm -R ab')->shouldNotBeCalled();
        $ssh->exec('rm -R 999')->shouldNotBeCalled();
        $ssh->exec('rm -R test')->shouldNotBeCalled();
    }

    public function testRemoveFailedRelease()
    {
        $ssh = $this->prophesize(SSH2::class);
        $ssh->setTimeout(0)->shouldBeCalled();
        $ssh->getExitStatus()->willReturn(0);

        $logger = $this->prophesize(ConsoleLogger::class);
        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal());

        $ssh->exec(Argument::any())->shouldBeCalled();
        $ssh->exec('if test -f "/home/wwwroot/automate/demo/releases/failed"; then echo "Y";fi')->willReturn('Y');

        $event = new DeployEvent($context);
        $listener = new ClearListener();
        $listener->removeFailedRelease($event);

        $ssh->exec('rm -R /home/wwwroot/automate/demo/releases/failed')->shouldBeCalled();
    }

    public function testMoveFailedRelease()
    {
        $ssh = $this->prophesize(SSH2::class);
        $ssh->setTimeout(0)->shouldBeCalled();
        $ssh->getExitStatus()->willReturn(0);
        $ssh->exec(Argument::any())->shouldBeCalled();
        $ssh->exec('mkdir -p /home/wwwroot/automate/demo/releases')->shouldBeCalled();

        $logger = $this->prophesize(ConsoleLogger::class);

        $session = new SSHSession($ssh->reveal());
        $context = $this->createContext($session, $logger->reveal());

        $event = new FailedDeployEvent($context, new \Exception());
        $listener = new ClearListener();
        $listener->moveFailedRelease($event);

        $ssh->exec(sprintf('mv /home/wwwroot/automate/demo/releases/%s /home/wwwroot/automate/demo/releases/failed', $context->getReleaseId()))
            ->shouldBeCalled();
    }

}
