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

use Automate\Logger\ConsoleLogger;
use Automate\Session\SSHSession;
use Automate\Tests\AbstractContextTestCase;
use Automate\Workflow;
use phpseclib3\Net\SSH2;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployTest extends AbstractContextTestCase
{
    public function testRemoteDeploy(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);
        $workflow = new Workflow\Deployer($context);

        $releaseId = $context->getReleaseId();

        $ssh->expects('exec')->with('mkdir -p /home/wwwroot/automate/demo/releases/'.$releaseId)->once();
        $ssh->expects('exec')->with(sprintf('cd /home/wwwroot/automate/demo/releases/%s; git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master .', $releaseId))->once();
        $ssh->expects('exec')->with(sprintf('cd /home/wwwroot/automate/demo/releases/%s; php -v', $releaseId))->once();
        $ssh->expects('exec')->with(sprintf('cd /home/wwwroot/automate/demo/releases/%s; composer install', $releaseId))->once();
        $ssh->expects('exec')->with(sprintf('ln -sfn /home/wwwroot/automate/demo/releases/%s /home/wwwroot/automate/demo/current', $releaseId))->once();

        $rs = $workflow->deploy();

        $this->assertTrue($rs);
    }

    public function testError(): void
    {
        $io = \Mockery::spy(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(1);
        $ssh->shouldReceive()->setTimeout(0);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);
        $workflow = new Workflow\Deployer($context);

        $rs = $workflow->deploy();

        $this->assertFalse($rs);
    }

    public function testCheckout(): void
    {
        $logger = \Mockery::spy(ConsoleLogger::class);

        $ssh = \Mockery::spy(SSH2::class);
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);
        $ssh->shouldReceive()->setTimeout(0);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger, 'master');
        $workflow = new Workflow\Deployer($context);

        $rs = $workflow->deploy();

        $this->assertTrue($rs);
    }
}
