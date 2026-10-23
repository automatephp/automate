<?php

namespace Automate\Tests\Workflow;

use Automate\Archiver;
use Automate\Loader;
use Automate\Logger\LoggerInterface;
use Automate\Model\Server;
use Automate\Tests\AbstractMockTestCase;
use Automate\Transport\TransportFactory;
use Automate\Transport\TransportInterface;
use Automate\Workflow\Context;
use Automate\Workflow\Deployer;
use Symfony\Component\Process\Process;

class DeployerTest extends AbstractMockTestCase
{
    public function testDeploy(): void
    {
        $loader = new Loader();
        $project = $loader->load(__DIR__.'/../../fixtures/simple.yml');
        $platform = $project->getPlatform('development');
        $logger = \Mockery::spy(LoggerInterface::class);

        $ssh = \Mockery::spy(TransportInterface::class);
        $sshFactory = \Mockery::spy(TransportFactory::class);
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
            ." AUTOMATE_PHASE='deploy' AUTOMATE_PLATFORM='development' AUTOMATE_SERVER='dddv-exemple-front-01' AUTOMATE_GIT_REF='master'; ";

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

    public function testFirstDeployHookRunsOnNewServersOnly(): void
    {
        [$commands, $sections] = $this->deployTwoServers(['web1' => true, 'web2' => false]);

        $this->assertContains('echo first', $commands['web1']);
        $this->assertContains('echo first-web1', $commands['web1']);
        $this->assertNotContains('echo first-web2', $commands['web1']);
        $this->assertNotContains('echo first', $commands['web2']);
        $this->assertNotContains('echo first-web1', $commands['web2']);
        $this->assertContains('echo deploy', $commands['web2']);

        $this->assertSame(
            ['Pre deploy', 'First deploy', 'On deploy', 'Publish new release', 'Post deploy', 'Clear olds releases'],
            $sections
        );
    }

    public function testFirstDeployHookIsSkippedWhenNoServerIsNew(): void
    {
        [$commands, $sections] = $this->deployTwoServers(['web1' => false, 'web2' => false]);

        foreach ($commands as $serverCommands) {
            $this->assertNotContains('echo first', $serverCommands);
        }

        $this->assertNotContains('First deploy', $sections);
    }

    /**
     * @param array<string, bool> $isNew
     *
     * @return array{array<string, string[]>, string[]}
     */
    private function deployTwoServers(array $isNew): array
    {
        $project = (new Loader())->load(__DIR__.'/../../fixtures/first_deploy.yml');
        $platform = $project->getPlatform('production');

        $commands = [];
        $sections = [];
        $logger = \Mockery::spy(LoggerInterface::class);
        $logger->shouldReceive('section')->andReturnUsing(static function (string $name) use (&$sections): void {
            $sections[] = $name;
        });

        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->andReturnUsing(static function (Server $server) use (&$commands, $isNew): TransportInterface {
            $name = $server->getName();
            $ssh = \Mockery::mock(TransportInterface::class);
            $ssh->shouldReceive('login');
            $ssh->shouldReceive('execAsync')->andReturnUsing(static function (string $command) use ($name, &$commands): Process {
                $commands[$name][] = preg_replace('/^export [^;]*; cd [^;&]* && /', '', $command);

                return new Process(['true']);
            });
            $ssh->shouldReceive('exec')->andReturnUsing(static function (string $command) use ($name, $isNew, &$commands): string {
                $commands[$name][] = preg_replace('/^export [^;]*; cd [^;&]* && /', '', $command);

                if (str_contains($command, 'test -e "/srv/'.$name.'/releases"')) {
                    return $isNew[$name] ? '' : 'Y';
                }

                if (str_starts_with($command, 'find /srv/'.$name.'/releases')) {
                    return $isNew[$name] ? '' : '/srv/'.$name.'/releases/2024.03.09-1200.123';
                }

                return '';
            });

            return $ssh;
        });

        $context = new Context($project, $platform, $logger, $sshFactory, \Mockery::mock(Archiver::class), releaseId: '2024.03.10-2340.241');
        $this->assertTrue((new Deployer($context))->deploy());

        return [$commands, array_values(array_filter($sections, static fn (string $s): bool => 'Start deployment' !== $s))];
    }
}
