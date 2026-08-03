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
use Automate\Ssh\Ssh;
use Automate\Ssh\SshFactory;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Context;
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
        $this->expectExceptionMessage($expected);

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
        $sshFactory = \Mockery::mock(SshFactory::class);

        // two servers, otherwise execAsync falls back on the synchronous path
        foreach (['front-01', 'front-02'] as $name) {
            $server = (new Server())->setName($name)->setPath('/home/wwwroot/demo');
            $platform->addServer($server);

            $ssh = \Mockery::spy(Ssh::class);
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
}
