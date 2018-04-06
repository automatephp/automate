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
use Automate\Tests\AbstractContextTest;
use Automate\Workflow;
use Phake;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployTest extends AbstractContextTest
{
    public function testRemoteDeploy()
    {
        $io = Phake::mock(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);
        $workflow = new Workflow\Deployer($context);

        $releaseId = $context->getReleaseId();

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

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger);
        $workflow = new Workflow\Deployer($context);

        $rs = $workflow->deploy();

        $this->assertFalse($rs);
    }

    public function testCheckout()
    {
        $logger = Phake::mock(ConsoleLogger::class);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        $session = new SSHSession($ssh);
        $context = $this->createContext($session, $logger, 'master');
        $workflow = new Workflow\Deployer($context);

        $rs = $workflow->deploy('1.0.0');

        $this->assertTrue($rs);
    }

    public function testLocalDeploy()
    {
        $io = Phake::mock(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $context = $this->createLocalContext($logger);
        
        $workflow = new Workflow\Deployer($context);
        $this->assertFalse($workflow->deploy());

//        Phake::inOrder(
//            Phake::verify($ssh)->exec("mkdir -p /home/wwwroot/automate/demo/releases/$releaseId"),
//            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master ."),
//            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; php -v"),
//            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; composer install"),
//            Phake::verify($ssh)->exec("ln -sfn /home/wwwroot/automate/demo/releases/$releaseId /home/wwwroot/automate/demo/current")
//        );
    }
}
