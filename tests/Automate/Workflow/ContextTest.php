<?php

namespace Automate\Tests\Workflow;

use Automate\Archiver;
use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Ssh\Ssh;
use Automate\Ssh\SshFactory;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Context;

class ContextTest extends AbstractMockTestCase
{
    public function testHooksReceiveTheDeploymentContext(): void
    {
        $command = $this->runHook('feat/x', ['slug' => 'feat-x']);

        foreach ([
            "AUTOMATE_PLATFORM='review'",
            "AUTOMATE_SERVER='vm'",
            "AUTOMATE_GIT_REF='feat/x'",
            "AUTOMATE_RELEASE_ID='2024.03.10-2340.241'",
            "AUTOMATE_RELEASE_PATH='/srv/review/releases/2024.03.10-2340.241'",
            "AUTOMATE_RELEASES_PATH='/srv/review/releases'",
            "AUTOMATE_SHARED_PATH='/srv/review/shared'",
            "AUTOMATE_CURRENT_PATH='/srv/review/current'",
            "AUTOMATE__slug='feat-x'",
        ] as $export) {
            $this->assertStringContainsString($export, $command);
        }

        $this->assertStringEndsWith('; cd /srv/review/releases/2024.03.10-2340.241; ./hook.sh', $command);
        $this->assertStringNotContainsString('s3cret', $command);
    }

    public function testGitRefFallsBackToTheDefaultBranch(): void
    {
        $this->assertStringContainsString("AUTOMATE_GIT_REF='master'", $this->runHook(null, []));
    }

    /**
     * @param array<string, string> $variables
     */
    private function runHook(?string $gitRef, array $variables): string
    {
        $server = new Server(name: 'vm', path: '/srv/review', password: 's3cret');
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        $platform->addServer($server);

        $executed = [];
        $ssh = \Mockery::mock(Ssh::class);
        $ssh->shouldReceive('login');
        $ssh->shouldReceive('exec')->andReturnUsing(static function (string $command) use (&$executed): string {
            $executed[] = $command;

            return '';
        });
        $sshFactory = \Mockery::mock(SshFactory::class);
        $sshFactory->shouldReceive('create')->andReturns($ssh);

        $context = new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, new Archiver(), $gitRef, releaseId: '2024.03.10-2340.241', variables: $variables);
        $context->connect();
        $context->exec('./hook.sh');

        $this->assertCount(1, $executed);

        return $executed[0];
    }
}
