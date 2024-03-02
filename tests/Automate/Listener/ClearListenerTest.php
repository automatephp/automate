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
use phpseclib\Net\SSH2;

class ClearListenerTest extends AbstractContextTest
{
    public function testClearReleases()
    {
        $ssh = \Mockery::mock(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);

        $ssh->expects()->exec('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->andReturns('
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

        $logger = \Mockery::spy(ConsoleLogger::class);
        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $event = new DeployEvent($context);
        $listener = new ClearListener();

        $ssh->shouldReceive('exec')->with('rm -R 2016.08.20-0032.620')->once();
        $ssh->shouldReceive('exec')->with('rm -R 2016.08.27-0032.620')->once();

        $listener->clearReleases($event);
    }

    public function testRemoveFailedRelease()
    {
        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);

        $logger = \Mockery::spy(ConsoleLogger::class);
        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $ssh->expects('exec')->with('if test -f "/home/wwwroot/automate/demo/releases/failed"; then echo "Y";fi')->andReturns('Y');
        $ssh->expects('exec')->with('rm -R /home/wwwroot/automate/demo/releases/failed');

        $event = new DeployEvent($context);
        $listener = new ClearListener();
        $listener->removeFailedRelease($event);
    }

    public function testMoveFailedRelease()
    {
        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);
        $ssh->expects('exec')->with('mkdir -p /home/wwwroot/automate/demo/releases')->once();

        $logger = \Mockery::spy(ConsoleLogger::class);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);

        $ssh->expects('exec')->with(sprintf('mv /home/wwwroot/automate/demo/releases/%s /home/wwwroot/automate/demo/releases/failed', $context->getReleaseId()))
            ->once();

        $event = new FailedDeployEvent($context, new \Exception());
        $listener = new ClearListener();
        $listener->moveFailedRelease($event);
    }
}
