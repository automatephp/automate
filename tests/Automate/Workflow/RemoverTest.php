<?php

namespace Automate\Tests\Workflow;

use Automate\Archiver;
use Automate\Logger\LoggerInterface;
use Automate\Model\Command;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Tests\AbstractMockTestCase;
use Automate\Transport\TransportFactory;
use Automate\Transport\TransportInterface;
use Automate\Workflow\Context;
use Automate\Workflow\Remover;

class RemoverTest extends AbstractMockTestCase
{
    private const string LOCK = '/srv/review/automate.lock';

    /** @var string[] */
    private array $executed = [];

    /** @var array<string, string|true> existing directories and files, a symlink holds its target */
    private array $paths = [];

    public function testRemoveRunsTheHooksInCurrentThenDeletesTheProject(): void
    {
        $this->paths = $this->fullProject();

        $this->assertTrue($this->remove([new Command('docker compose down')]));

        $env = "AUTOMATE_PHASE='remove' AUTOMATE_PLATFORM='review' AUTOMATE_SERVER='vm'";
        $hook = array_values(array_filter($this->executed, static fn (string $command): bool => str_contains($command, 'docker compose down')));
        $this->assertCount(1, $hook);
        $this->assertStringContainsString("AUTOMATE_RELEASE_ID='2024.03.09-1200.123'", $hook[0]);
        $this->assertStringContainsString("AUTOMATE_RELEASE_PATH='/srv/review/releases/2024.03.09-1200.123'", $hook[0]);
        $this->assertStringContainsString($env, $hook[0]);
        $this->assertStringNotContainsString('AUTOMATE_GIT_REF', $hook[0]);
        $this->assertStringEndsWith('; cd /srv/review/current && docker compose down', $hook[0]);

        $this->assertSame([
            'touch '.self::LOCK,
            'hook',
            'rm -R /srv/review/current',
            'rm -R /srv/review/releases',
            'rm -R /srv/review/shared',
            'rm '.self::LOCK,
            'rmdir /srv/review',
        ], $this->summary());
        $this->assertSame([], $this->paths);
    }

    public function testKeepSharedKeepsTheSharedFolderAndTheProjectPath(): void
    {
        $this->paths = $this->fullProject();

        $this->assertTrue($this->remove([], keepShared: true));

        $this->assertNotContains('rm -R /srv/review/shared', $this->executed);
        $this->assertNotContains('rmdir /srv/review', $this->executed);
        $this->assertArrayHasKey('/srv/review/shared', $this->paths);
        $this->assertArrayNotHasKey(self::LOCK, $this->paths);
    }

    public function testSharedPathOutsideOfTheProjectIsNeverRemoved(): void
    {
        $this->paths = $this->fullProject() + ['/srv/common-shared' => true];

        $this->assertTrue($this->remove([], sharedPath: '/srv/common-shared'));

        $this->assertNotContains('rm -R /srv/common-shared', $this->executed);
        $this->assertArrayHasKey('/srv/common-shared', $this->paths);
    }

    public function testSharedPathThatEscapesTheProjectIsNeverRemoved(): void
    {
        $this->paths = $this->fullProject() + ['/srv' => true];

        $this->assertTrue($this->remove([], sharedPath: '/srv/review/../'));

        $this->assertNotContains('rm -R /srv/review/../', $this->executed);
        $this->assertNotContains('rm -R /srv', $this->executed);
    }

    /**
     * The path is compared in its canonical form, but the server knows it as written.
     */
    public function testSharedPathIsRemovedAsConfigured(): void
    {
        $this->paths = $this->fullProject();

        $this->assertTrue($this->remove([], sharedPath: '/srv/review/shared/'));

        $this->assertContains('rm -R /srv/review/shared/', $this->executed);
        $this->assertNotContains('rm -R /srv/review/shared', $this->executed);
    }

    public function testFailingHookStopsTheRemovalAndReleasesTheLock(): void
    {
        $this->paths = $this->fullProject();

        $this->assertFalse($this->remove([new Command('failing-hook'), new Command('never-run')]));

        $this->assertSame(['touch '.self::LOCK, 'hook', 'rm '.self::LOCK], $this->summary());
        $this->assertArrayHasKey('/srv/review/releases', $this->paths);
        $this->assertArrayHasKey('/srv/review/current', $this->paths);
        $this->assertArrayHasKey('/srv/review/shared', $this->paths);
    }

    public function testFailingHookOnOneServerLeavesEveryServerUntouched(): void
    {
        $this->paths = $this->fullProject('/srv/a') + $this->fullProject('/srv/b');

        $this->assertFalse($this->remove([new Command('failing-hook on b')], servers: ['a' => '/srv/a', 'b' => '/srv/b']));

        $this->assertSame([
            'touch /srv/a/automate.lock',
            'touch /srv/b/automate.lock',
            'hook',
            'hook',
            'rm /srv/a/automate.lock',
            'rm /srv/b/automate.lock',
        ], $this->summary());
        $this->assertSame($this->fullProject('/srv/a') + $this->fullProject('/srv/b'), $this->paths);
    }

    public function testMissingProjectPathIsANoop(): void
    {
        $this->assertTrue($this->remove([new Command('docker compose down')]));

        $this->assertSame([], $this->summary());
    }

    public function testProjectPathIsKeptWhenSomethingElseLivesInIt(): void
    {
        $this->paths = $this->fullProject() + ['/srv/review/other' => true];

        $this->assertTrue($this->remove([]));

        $this->assertNotContains('rmdir /srv/review', $this->executed);
        $this->assertArrayHasKey('/srv/review', $this->paths);
    }

    public function testExistingLockFileRefusesTheRemoval(): void
    {
        $this->paths = $this->fullProject() + [self::LOCK => true];

        $this->assertFalse($this->remove([new Command('docker compose down')]));

        $this->assertSame([], $this->summary());
        $this->assertArrayHasKey(self::LOCK, $this->paths);
    }

    public function testForceIgnoresAnExistingLockFile(): void
    {
        $this->paths = $this->fullProject() + [self::LOCK => true];

        $this->assertTrue($this->remove([], force: true));

        $this->assertSame([], $this->paths);
    }

    public function testHooksWithoutCurrentReleaseAreSkipped(): void
    {
        $this->paths = ['/srv/review' => true, '/srv/review/releases' => true];

        $this->assertTrue($this->remove([new Command('docker compose down')]));

        $this->assertSame(['touch '.self::LOCK, 'rm -R /srv/review/releases', 'rm '.self::LOCK, 'rmdir /srv/review'], $this->summary());
    }

    /**
     * @return array<string, string|true>
     */
    private function fullProject(string $path = '/srv/review'): array
    {
        return [
            $path => true,
            $path.'/releases' => true,
            $path.'/releases/2024.03.09-1200.123' => true,
            $path.'/current' => $path.'/releases/2024.03.09-1200.123',
            $path.'/shared' => true,
        ];
    }

    /**
     * The commands that change something, hooks shown as "hook".
     *
     * @return string[]
     */
    private function summary(): array
    {
        $summary = [];
        foreach ($this->executed as $command) {
            if (str_contains($command, '/current && ')) {
                $summary[] = 'hook';
            } elseif (1 === preg_match('/^(touch|rm|rmdir) /', $command)) {
                $summary[] = $command;
            }
        }

        return $summary;
    }

    /**
     * @param Command[]             $hooks
     * @param array<string, string> $servers paths indexed by server name
     */
    private function remove(array $hooks, bool $keepShared = false, ?string $sharedPath = null, bool $force = false, array $servers = ['vm' => '/srv/review']): bool
    {
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        foreach ($servers as $name => $path) {
            $platform->addServer(new Server(name: $name, path: $path, sharedPath: $sharedPath));
        }

        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('login');
        $ssh->shouldReceive('exec')->andReturnUsing($this->fakeServer(...));
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->andReturns($ssh);

        $context = new Context((new Project())->setOnRemove($hooks), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, new Archiver(), force: $force, releaseId: '2024.03.10-2340.241', phase: 'remove');

        return (new Remover($context, $keepShared))->remove();
    }

    private function fakeServer(string $command): string
    {
        $this->executed[] = $command;

        // "-L" makes a dangling symlink exist too: only the entry matters here
        if (1 === preg_match('/^if test -e "(.+)" \|\| test -L "\1"; then/', $command, $matches)) {
            return isset($this->paths[rtrim($matches[1], '/')]) ? 'Y' : '';
        }

        if (1 === preg_match('/^(?:mkdir -p|touch) (.+)$/', $command, $matches)) {
            $this->paths[$matches[1]] = true;
        } elseif (1 === preg_match('/^rm(?:dir| -R)? (.+)$/', $command, $matches)) {
            $removed = rtrim($matches[1], '/');
            unset($this->paths[$removed]);
            foreach (array_keys($this->paths) as $path) {
                if (str_starts_with($path, $removed.'/')) {
                    unset($this->paths[$path]);
                }
            }
        } elseif (1 === preg_match('/^readlink (\S+)/', $command, $matches)) {
            $entry = $this->paths[$matches[1]] ?? null;

            return is_string($entry) ? $entry."\n" : '';
        } elseif (1 === preg_match('/^ls -A (.+)$/', $command, $matches)) {
            return implode("\n", array_map(basename(...), array_filter(array_keys($this->paths), static fn (string $path): bool => dirname($path) === $matches[1])));
        } elseif (1 === preg_match('/failing-hook(?: on (\S+))?$/', $command, $matches)) {
            if (!isset($matches[1]) || str_contains($command, sprintf("AUTOMATE_SERVER='%s'", $matches[1]))) {
                throw new \RuntimeException('hook failed');
            }
        }

        return '';
    }
}
