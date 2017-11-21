<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Workflow;

use Automate\Loader;
use Automate\Logger\ConsoleLogger;
use Automate\Logger\LoggerInterface;
use Automate\Session;
use Automate\SessionFactory;
use Automate\Workflow;
use Phake;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Style\SymfonyStyle;
use Automate\PluginManager;

class DeployTest extends \PHPUnit_Framework_TestCase
{
    public function testDeploy()
    {
        $io = Phake::mock(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        $session = new Session($ssh);
        $workflow = $this->createWorkflow($session, $logger);

        $releaseId = $workflow->getReleaseId();

        $rs = $workflow->deploy();

        $this->assertTrue($rs);

        Phake::inOrder(
            Phake::verify($ssh)->exec("mkdir -p /home/wwwroot/automate/demo/releases/$releaseId"),
            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master ."),
            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; php -v"),
            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; composer install"),
            Phake::verify($ssh)->exec("ln -sfn /home/wwwroot/automate/demo/releases/$releaseId /home/wwwroot/automate/demo/current")
        );
    }

    public function testError()
    {
        $io = Phake::mock(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(1);

        $session = new Session($ssh);
        $workflow = $this->createWorkflow($session, $logger);

        $rs = $workflow->deploy();

        $this->assertFalse($rs);
    }

    public function testCheckout()
    {
        $logger = Phake::mock(ConsoleLogger::class);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        $session = new Session($ssh);
        $workflow = $this->createWorkflow($session, $logger);

        $rs = $workflow->deploy('1.0.0');

        $this->assertTrue($rs);
    }

    public function testClearReleases()
    {
        $logger = Phake::mock(ConsoleLogger::class);
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);
        Phake::when($ssh)->exec('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->thenReturn('
            2016.08.30-0032.620
            ab
            2016.08.28-0032.620
            999
            2016.08.27-0032.620
            test
            2016.08.29-0032.620
            2016.08.22-0032.620-failed
        ');
        $session = new Session($ssh);
        $workflow = $this->createWorkflow($session, $logger);
        $rs = $workflow->deploy('1.0.0');
        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.27-0032.620');
        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.22-0032.620-failed');

        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.28-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.29-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.30-0032.620');
        Phake::verify($ssh, Phake::never(0))->exec('rm -R ab');
        Phake::verify($ssh, Phake::never(0))->exec('rm -R 999');
        Phake::verify($ssh, Phake::never(0))->exec('rm -R test');

        $this->assertTrue($rs);
    }

    public function testClearReleasesFailed()
    {
        $logger = Phake::mock(ConsoleLogger::class);
        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        Phake::when($ssh)->exec('mkdir -p /home/wwwroot/automate/demo/shared/app/config')->thenThrow(new \RuntimeException());

        Phake::when($ssh)->exec('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->thenReturn('
            2016.08.24-0033.620-failed
            2016.08.25-0033.620-failed
            2016.08.27-0032.620
            2016.08.27-0033.620-failed
            2016.08.28-0032.620
            2016.08.29-0032.620
            2016.08.29-0034.620-failed
        ');
        $session = new Session($ssh);
        $workflow = $this->createWorkflow($session, $logger);
        $rs = $workflow->deploy('1.0.1');

        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.24-0033.620-failed');
        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.25-0033.620-failed');
        Phake::verify($ssh, Phake::times(1))->exec('rm -R 2016.08.27-0033.620-failed');
        
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.27-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.28-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.29-0032.620');
        Phake::verify($ssh, Phake::times(0))->exec('rm -R 2016.08.29-0034.620-failed');

        $this->assertFalse($rs);
    }

    private function createWorkflow(Session $session, LoggerInterface $logger)
    {
        $pluginManger = new PluginManager();
        $loader = new Loader($pluginManger);

        $project = $loader->load(__DIR__.'/../../fixtures/simple.yml');
        $platform = $project->getPlatform('development');

        $sessionFactory = Phake::mock(SessionFactory::class);
        Phake::when($sessionFactory)->create(current($platform->getServers()))->thenReturn($session);

        $workflow = new Workflow\Deployer($project, $platform, $logger, $pluginManger, $sessionFactory);

        return $workflow;
    }
}
