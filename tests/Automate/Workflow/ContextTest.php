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

use Automate\Archiver;
use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Tests\AbstractMockTestCase;
use Automate\Transport\TransportFactory;
use Automate\Transport\TransportInterface;
use Automate\Workflow\Context;
use Automate\Workflow\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

class ContextTest extends AbstractMockTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function failingCommandProvider(): iterable
    {
        yield 'reported on the error output' => ['echo "boom" >&2; exit 1', 'boom'];

        // this is how the "exec" command reports a failure
        yield 'reported on the standard output' => ['echo "boom"; exit 1', 'boom'];

        yield 'not reported at all' => ['exit 3', 'The command failed with exit code 3.'];
    }

    #[DataProvider('failingCommandProvider')]
    public function testExecAsyncReportsWhyTheCommandFailed(string $script, string $expected): void
    {
        $context = $this->createContext(static fn (): Process => new Process(['sh', '-c', $script]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('[front-01] '.$expected);

        $context->execAsync('a command');
    }

    public function testExecAsyncSucceeds(): void
    {
        $context = $this->createContext(static fn (): Process => new Process(['sh', '-c', 'exit 0']));

        $context->execAsync('a command');

        $this->addToAssertionCount(1);
    }

    /**
     * @param callable(): Process $process
     */
    private function createContext(callable $process): Context
    {
        $platform = new Platform('production');
        $sshFactory = \Mockery::mock(TransportFactory::class);

        // two servers, otherwise execAsync falls back on the synchronous path
        foreach (['front-01', 'front-02'] as $name) {
            $server = (new Server())->setName($name)->setPath('/home/wwwroot/demo');
            $platform->addServer($server);

            $ssh = \Mockery::spy(TransportInterface::class);
            $ssh->shouldReceive('execAsync')->andReturnUsing($process);
            $sshFactory->shouldReceive('create')->with($server)->andReturns($ssh);
        }

        $context = new Context(
            new Project(),
            $platform,
            \Mockery::spy(LoggerInterface::class),
            $sshFactory,
            \Mockery::mock(Archiver::class),
            releaseId: '2024.03.10-2340.241'
        );

        $context->connect();

        return $context;
    }

    public function testReleaseIdsOfTheSameMinuteAreSortedChronologically(): void
    {
        $ids = array_map(
            static fn (string $time): string => Context::createReleaseId(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2024-03-10 23:40:'.$time)),
            ['00.000', '07.950', '08.050', '59.999'],
        );

        $this->assertSame(['2024.03.10-2340.000', '2024.03.10-2340.079', '2024.03.10-2340.080', '2024.03.10-2340.599'], $ids);
        $sorted = $ids;
        sort($sorted);
        $this->assertSame($ids, $sorted);

        foreach ($ids as $id) {
            $this->assertTrue(Session::isReleaseId($id));
        }
    }

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

        $this->assertStringEndsWith('; cd /srv/review/releases/2024.03.10-2340.241 && ./hook.sh', $command);
        $this->assertStringNotContainsString('s3cret', $command);
    }

    public function testGitRefFallsBackToTheDefaultBranch(): void
    {
        $this->assertStringContainsString("AUTOMATE_GIT_REF='master'", $this->runHook(null, []));
    }

    public function testRemovalHooksExposeThePhaseAndNoGitRef(): void
    {
        $command = $this->runHook('feat/x', [], 'remove');

        $this->assertStringContainsString("AUTOMATE_PHASE='remove'", $command);
        $this->assertStringNotContainsString('AUTOMATE_GIT_REF', $command);
        $this->assertStringContainsString("AUTOMATE_PHASE='deploy'", $this->runHook(null, []));
    }

    public function testConnectCanTargetASingleServer(): void
    {
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        $platform->addServer(new Server(name: 'a', path: '/srv/a'));
        $platform->addServer(new Server(name: 'b', path: '/srv/b'));

        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('login')->once();
        $ssh->shouldReceive('exec')->andReturns('');
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->once()->with(\Mockery::on(static fn (Server $server): bool => 'b' === $server->getName()))->andReturns($ssh);

        (new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, new Archiver()))->connect('b');
    }

    public function testConnectRejectsAnUnknownServer(): void
    {
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        $platform->addServer(new Server(name: 'a', path: '/srv/a'));

        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldNotReceive('create');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing server c');

        (new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, new Archiver()))->connect('c');
    }

    /**
     * @param array<string, string> $variables
     */
    private function runHook(?string $gitRef, array $variables, string $phase = 'deploy'): string
    {
        $server = new Server(name: 'vm', path: '/srv/review', password: 's3cret');
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        $platform->addServer($server);

        $executed = [];
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('login');
        $ssh->shouldReceive('exec')->andReturnUsing(static function (string $command) use (&$executed): string {
            $executed[] = $command;

            return '';
        });
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->andReturns($ssh);

        $context = new Context(new Project(), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, new Archiver(), $gitRef, releaseId: '2024.03.10-2340.241', variables: $variables, phase: $phase);
        $context->connect();
        $context->exec('./hook.sh');

        return end($executed);
    }

    public function testFirstDeploymentIsDetectedPerServer(): void
    {
        $platform = new Platform(name: 'prod', defaultBranch: 'master');
        $platform->addServer(new Server(name: 'new', path: '/srv/new'));
        $platform->addServer(new Server(name: 'old', path: '/srv/old'));

        $executed = [];
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->andReturnUsing(static function (Server $server) use (&$executed): TransportInterface {
            $ssh = \Mockery::mock(TransportInterface::class);
            $ssh->shouldReceive('login');
            $ssh->shouldReceive('exec')->andReturnUsing(static function (string $command) use ($server, &$executed): string {
                $executed[$server->getName()][] = $command;

                return match (true) {
                    'old' === $server->getName() && str_contains($command, 'test -e') => 'Y',
                    'old' === $server->getName() && str_starts_with($command, 'find') => '/srv/old/releases/2024.03.09-1200.123',
                    default => '',
                };
            });

            return $ssh;
        });

        $logger = \Mockery::spy(LoggerInterface::class);
        $context = new Context(new Project(), $platform, $logger, $sshFactory, new Archiver(), releaseId: '2024.03.10-2340.241', variables: ['slug' => 'x']);
        $context->connect();
        $context->exec('./hook.sh');

        $this->assertSame(['new'], $context->getFirstDeployServers());
        $this->assertStringContainsString("AUTOMATE_FIRST_DEPLOY='1'", end($executed['new']));
        $this->assertStringContainsString("AUTOMATE_FIRST_DEPLOY='0'", end($executed['old']));
        $logger->shouldHaveReceived('info')->with('First deployment', \Mockery::on(static fn (Server $server): bool => 'new' === $server->getName()))->once();
    }

    public function testFirstDeploymentIsNotLookedUpOutsideOfADeployment(): void
    {
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        $platform->addServer(new Server(name: 'vm', path: '/srv/review'));

        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('login')->once();
        $ssh->shouldNotReceive('exec');
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->andReturns($ssh);

        $logger = \Mockery::spy(LoggerInterface::class);
        (new Context(new Project(), $platform, $logger, $sshFactory, new Archiver(), phase: 'remove'))->connect();

        $logger->shouldNotHaveReceived('info');
    }
}
