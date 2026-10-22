<?php

namespace Automate\Tests\Workflow;

use Automate\Archiver;
use Automate\Loader;
use Automate\Logger\LoggerInterface;
use Automate\Ssh\Ssh;
use Automate\Ssh\SshFactory;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Context;
use Automate\Workflow\Deployer;

class DeployerTest extends AbstractMockTestCase
{
    public function testDeploy(): void
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../../fixtures/simple.yml');
        $platform = $project->getPlatform('development');
        $logger = \Mockery::spy(LoggerInterface::class);

        $ssh = \Mockery::spy(Ssh::class);
        $sshFactory = \Mockery::spy(SshFactory::class);
        $sshFactory->shouldReceive('create')->andReturns($ssh);

        $archiver = \Mockery::mock(Archiver::class);

        $context = new Context($project, $platform, $logger, $sshFactory, $archiver, releaseId: '2024.03.10-2340.241');
        $deployer = new Deployer($context);

        // create project folder
        $ssh->expects('exec')->with('mkdir -p /home/wwwroot/automate/demo')->once();

        // add lock file
        $ssh->expects('exec')->with('touch /home/wwwroot/automate/demo/automate.lock')->once();

        // create releases folder
        $ssh->expects('exec')->with('mkdir -p /home/wwwroot/automate/demo/releases/2024.03.10-2340.241')->once();

        $env = "export AUTOMATE_RELEASE_ID='2024.03.10-2340.241'"
            ." AUTOMATE_RELEASE_PATH='/home/wwwroot/automate/demo/releases/2024.03.10-2340.241'"
            ." AUTOMATE_RELEASES_PATH='/home/wwwroot/automate/demo/releases'"
            ." AUTOMATE_SHARED_PATH='/home/wwwroot/automate/demo/shared'"
            ." AUTOMATE_CURRENT_PATH='/home/wwwroot/automate/demo/current'"
            ." AUTOMATE_FIRST_DEPLOY='1'"
            ." AUTOMATE_PLATFORM='development' AUTOMATE_SERVER='dddv-exemple-front-01' AUTOMATE_GIT_REF='master'; ";

        // gi clone
        $ssh->expects('exec')->with($env.'cd /home/wwwroot/automate/demo/releases/2024.03.10-2340.241 && git clone git@github.com:julienj/symfony-demo.git -q --recursive -b master .')->once();

        // hook pre_deploy
        $ssh->expects('exec')->with($env.'cd /home/wwwroot/automate/demo/releases/2024.03.10-2340.241 && php -v')->once();

        $ssh->expects('exec')->with('ln -sfn /home/wwwroot/automate/demo/shared/app/data /home/wwwroot/automate/demo/releases/2024.03.10-2340.241/app/data')->once();
        $ssh->expects('exec')->with('ln -sfn /home/wwwroot/automate/demo/shared/app/config/parameters.yml /home/wwwroot/automate/demo/releases/2024.03.10-2340.241/app/config/parameters.yml')->once();

        // deploy
        $ssh->expects('exec')->with('ln -sfn /home/wwwroot/automate/demo/releases/2024.03.10-2340.241 /home/wwwroot/automate/demo/current')->once();

        // remove lock file
        $ssh->expects('exec')->with('rm /home/wwwroot/automate/demo/automate.lock')->once();

        // search old releases
        $ssh->expects('exec')->with('find /home/wwwroot/automate/demo/releases -maxdepth 1 -mindepth 1 -type d')->once();

        $deployer->deploy();
    }
}
