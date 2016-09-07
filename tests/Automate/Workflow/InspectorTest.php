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

class InspectorTest extends \PHPUnit_Framework_TestCase
{
    public function testInspect()
    {
        $io = Phake::mock(SymfonyStyle::class);
        $logger = new ConsoleLogger($io);

        $ssh = Phake::mock(SSH2::class);
        Phake::when($ssh)->getExitStatus()->thenReturn(0);

        $session = new Session($ssh);
        $workflow = $this->createInspector($session, $logger);
        $releaseId = $workflow->getReleaseId();
        $rs = $workflow->inspect();

        $this->assertTrue($rs);

        Phake::inOrder(
            Phake::verify($ssh)->exec("cd /home/wwwroot/automate/demo/releases/$releaseId; if [ ! -n \"$(grep \"^github.com \" ~/.ssh/known_hosts)\" ]; then ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null; fi"),
            Phake::verify($ssh)->exec("git ls-remote git@github.com:julienj/symfony-demo.git")
        );
    }

    private function createInspector(Session $session, LoggerInterface $logger)
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../../fixtures/simple.yml');
        $platform = $project->getPlatform('development');

        $sessionFactory = Phake::mock(SessionFactory::class);
        Phake::when($sessionFactory)->create(current($platform->getServers()))->thenReturn($session);

        $workflow = new Workflow\Inspector($project, $platform, $logger, $sessionFactory);

        return $workflow;
    }
}