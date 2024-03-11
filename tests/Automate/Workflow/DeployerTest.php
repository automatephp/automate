<?php

namespace Automate\Tests\Workflow;

use Automate\Loader;
use Automate\Logger\LoggerInterface;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Context;
use Automate\Workflow\Deployer;
use Automate\Workflow\Session;
use Automate\Workflow\SessionFactory;
use phpseclib3\Net\SFTP;

class DeployerTest extends AbstractMockTestCase
{
    public function testDeploy(): void
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../../fixtures/simple.yml');
        $platform = $project->getPlatform('development');
        $server = current($platform->getServers());
        $logger = \Mockery::spy(LoggerInterface::class);

        $sftp = \Mockery::spy(SFTP::class);
        $sftp->shouldReceive('getExitStatus')->andReturns(0);

        $session = new Session($server, $sftp, '2024.03.10-2340.241');
        $sessionFactory = \Mockery::spy(SessionFactory::class);
        $sessionFactory->expects('create')->andReturns($session)->once();

        $context = new Context($project, $platform, $logger, sessionFactory: $sessionFactory);
        $deployer = new Deployer($context);

        // create project folder
        $sftp->expects('exec')->with('mkdir -p /home/wwwroot/automate/demo')->once();

        // add lock file
        $sftp->expects('exec')->with('touch /home/wwwroot/automate/demo/automate.lock')->once();

        // create releases folder
        $sftp->expects('exec')->with('mkdir -p /home/wwwroot/automate/demo/releases/2024.03.10-2340.241')->once();

        // gi clone
        $sftp->expects('exec')->with('cd /home/wwwroot/automate/demo/releases/2024.03.10-2340.241; git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master .')->once();

        // hook pre_deploy
        $sftp->expects('exec')->with('cd /home/wwwroot/automate/demo/releases/2024.03.10-2340.241; php -v')->once();

        $sftp->expects('exec')->with('ln -sfn /home/wwwroot/automate/demo/shared/app/data /home/wwwroot/automate/demo/releases/2024.03.10-2340.241/app/data')->once();
        $sftp->expects('exec')->with('ln -sfn /home/wwwroot/automate/demo/shared/app/config/parameters.yml /home/wwwroot/automate/demo/releases/2024.03.10-2340.241/app/config/parameters.yml')->once();

        // deploy
        $sftp->expects('exec')->with('ln -sfn /home/wwwroot/automate/demo/releases/2024.03.10-2340.241 /home/wwwroot/automate/demo/current')->once();

        // remove lock file
        $sftp->expects('exec')->with('rm /home/wwwroot/automate/demo/automate.lock')->once();

        // search old releases
        $sftp->expects('exec')->with('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->once();

        $deployer->deploy();
    }
}
