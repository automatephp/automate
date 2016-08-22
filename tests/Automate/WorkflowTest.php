<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests;

use Automate\Loader;
use Automate\Logger\ConsoleLogger;
use Automate\Logger\LoggerInterface;
use Automate\Session;
use Automate\SessionFactory;
use Automate\Workflow;
use Phake;
use Symfony\Component\Console\Style\SymfonyStyle;

class WorkflowTest extends \PHPUnit_Framework_TestCase
{
    public function testDeploy()
    {
        $io  = Phake::mock(SymfonyStyle::class);
        $session = Phake::mock(Session::class);
        $logger = Phake::partialMock(ConsoleLogger::class, $io);

        $workflow = $this->createWorkflow($session, $logger);

        $workflow->deploy();

        Phake::inOrder(
            Phake::verify($logger)->section('Remote servers connection'),
            Phake::verify($logger)->section('Prepare Release'),
            Phake::verify($logger)->command('git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master .'),
            Phake::verify($logger)->section('Pre deploy'),
            Phake::verify($logger)->command('php -v'),
            Phake::verify($logger)->section('Setting up shared items'),
            Phake::verify($logger)->section('On deploy'),
            Phake::verify($logger)->command('composer install'),
            Phake::verify($logger)->section('Publish new release'),
            Phake::verify($logger)->section('Clear olds releases')
        );

    }

    private function createWorkflow(Session $session, LoggerInterface $logger)
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../fixtures/simple.yml');
        $platform = $project->getPlatform('development');

        $sessionFactory = Phake::mock(SessionFactory::class);
        Phake::when($sessionFactory)->create(current($platform->getServers()))->thenReturn($session);

        $workflow = new Workflow($project, $platform, $logger, $sessionFactory);

        return $workflow;
    }
}