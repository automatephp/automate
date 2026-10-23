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
use Automate\Workflow\Rollbacker;
use PHPUnit\Framework\Attributes\TestWith;

class RollbackerTest extends AbstractMockTestCase
{
    private const string LOCK = '/srv/review/automate.lock';

    private const string OLD = '2024.03.09-1200.123';

    private const string LIVE = '2024.03.10-1200.456';

    private const string FAILED = '/srv/review/releases/failed';

    /** @var string[] */
    private array $executed = [];

    /** @var array<string, string|true> existing directories and files, a symlink holds its target */
    private array $paths = [];

    public function testRollbackPointsCurrentToThePreviousReleaseAndMovesTheLeftOneToFailed(): void
    {
        $this->paths = $this->project();

        $this->assertTrue($this->rollback());

        $this->assertSame([
            'touch '.self::LOCK,
            'ln -sfn /srv/review/releases/'.self::OLD.' /srv/review/current',
            'mv /srv/review/releases/'.self::LIVE.' '.self::FAILED,
            'rm '.self::LOCK,
        ], $this->summary());
        $this->assertSame('/srv/review/releases/'.self::OLD, $this->paths['/srv/review/current']);
        $this->assertArrayHasKey('/srv/review/releases/'.self::OLD, $this->paths);
        $this->assertArrayNotHasKey('/srv/review/releases/'.self::LIVE, $this->paths);
        $this->assertArrayHasKey(self::FAILED, $this->paths);
    }

    public function testAPreviousFailedReleaseIsReplacedNotNested(): void
    {
        $this->paths = $this->project() + [self::FAILED => true, self::FAILED.'/composer.json' => true];

        $this->assertTrue($this->rollback());

        $this->assertContains('rm -R '.self::FAILED, $this->summary());
        $this->assertArrayHasKey(self::FAILED, $this->paths);
        $this->assertArrayNotHasKey(self::FAILED.'/composer.json', $this->paths);
        $this->assertArrayNotHasKey(self::FAILED.'/'.self::LIVE, $this->paths);
    }

    public function testASecondRollbackGoesOneReleaseFurtherBack(): void
    {
        $this->paths = $this->project(['2024.03.08-0900.100', self::OLD]);

        $this->assertTrue($this->rollback());
        $this->assertTrue($this->rollback());

        $this->assertSame('/srv/review/releases/2024.03.08-0900.100', $this->paths['/srv/review/current']);
        $this->assertArrayNotHasKey('/srv/review/releases/'.self::OLD, $this->paths);
    }

    public function testPreviousIsTheReleaseJustBeforeCurrentWhateverTheListingOrder(): void
    {
        $this->paths = $this->project(['2024.03.08-0900.100', self::OLD, '2024.03.11-0800.999']);

        $this->assertTrue($this->rollback());

        $this->assertSame('/srv/review/releases/'.self::OLD, $this->paths['/srv/review/current']);
        $this->assertArrayHasKey('/srv/review/releases/2024.03.11-0800.999', $this->paths);
        $this->assertArrayHasKey('/srv/review/releases/2024.03.08-0900.100', $this->paths);
    }

    public function testExplicitReleaseIsUsed(): void
    {
        $this->paths = $this->project(['2024.03.08-0900.100', self::OLD]);

        $this->assertTrue($this->rollback(release: '2024.03.08-0900.100'));

        $this->assertSame('/srv/review/releases/2024.03.08-0900.100', $this->paths['/srv/review/current']);
        $this->assertArrayHasKey('/srv/review/releases/'.self::OLD, $this->paths);
    }

    public function testUnknownOrCurrentReleaseIsRefusedWithoutChange(): void
    {
        $this->paths = $this->project();
        $before = $this->paths;

        foreach (['2020.01.01-0000.000', self::LIVE, '../'.self::OLD, 'failed'] as $release) {
            $this->assertFalse($this->rollback(release: $release), $release);
        }

        $this->assertSame($before, $this->paths);
        $this->assertSame([], $this->changes());
    }

    public function testNoPreviousReleaseIsRefusedWithoutChange(): void
    {
        $this->paths = $this->project([]);
        $before = $this->paths;

        $this->assertFalse($this->rollback());

        $this->assertSame($before, $this->paths);
        $this->assertSame(['touch '.self::LOCK, 'rm '.self::LOCK], $this->summary());
    }

    public function testMissingCurrentIsRefused(): void
    {
        $this->paths = ['/srv/review' => true, '/srv/review/releases' => true, '/srv/review/releases/'.self::OLD => true];

        $this->assertFalse($this->rollback());

        $this->assertArrayHasKey('/srv/review/releases/'.self::OLD, $this->paths);
        $this->assertNotContains('ln -sfn /srv/review/releases/'.self::OLD.' /srv/review/current', $this->executed);
    }

    #[TestWith(['/srv/review/shared'])]
    #[TestWith(['/srv/other/2024.03.10-1200.777'])]
    #[TestWith(['/srv/review/releases/2024.03.10-1200.999'])]
    public function testCurrentNotPointingToAListedReleaseIsRefused(string $target): void
    {
        $this->paths = $this->project();
        $this->paths['/srv/review/current'] = $target;

        $this->assertFalse($this->rollback());

        $this->assertSame($target, $this->paths['/srv/review/current']);
        $this->assertSame([], $this->changes());
    }

    public function testServersThatDisagreeAreLeftUntouched(): void
    {
        $this->paths = $this->project(servers: ['/srv/a' => [self::OLD], '/srv/b' => ['2024.03.09-1300.000', self::OLD]]);
        $before = $this->paths;

        $this->assertFalse($this->rollback(servers: ['a' => '/srv/a', 'b' => '/srv/b']));

        $this->assertSame($before, array_diff_key($this->paths, ['/srv/a/automate.lock' => 1, '/srv/b/automate.lock' => 1]));
    }

    public function testPostRollbackHooksRunInTheRestoredRelease(): void
    {
        $this->paths = $this->project();

        $this->assertTrue($this->rollback([new Command('php-fpm reload')]));

        $hook = array_values(array_filter($this->executed, static fn (string $command): bool => str_contains($command, 'php-fpm reload')));
        $this->assertCount(1, $hook);
        $this->assertStringContainsString("AUTOMATE_PHASE='rollback'", $hook[0]);
        $this->assertStringContainsString("AUTOMATE_RELEASE_ID='".self::OLD."'", $hook[0]);
        $this->assertStringContainsString("AUTOMATE_RELEASE_PATH='/srv/review/releases/".self::OLD."'", $hook[0]);
        $this->assertStringNotContainsString('AUTOMATE_GIT_REF', $hook[0]);
        $this->assertStringEndsWith('; cd /srv/review/current && php-fpm reload', $hook[0]);

        $this->assertSame(['touch', 'ln', 'hook', 'mv', 'rm'], array_map(static fn (string $line): string => explode(' ', $line)[0], $this->summary()));
    }

    public function testPostRollbackHooksHonourTheOnlyOption(): void
    {
        $this->paths = $this->project(servers: ['/srv/a' => [self::OLD], '/srv/b' => [self::OLD]]);

        $this->assertTrue($this->rollback([new Command('php-fpm reload', ['b'])], servers: ['a' => '/srv/a', 'b' => '/srv/b']));

        $hook = array_values(array_filter($this->executed, static fn (string $command): bool => str_contains($command, 'php-fpm reload')));
        $this->assertCount(1, $hook);
        $this->assertStringContainsString("AUTOMATE_SERVER='b'", $hook[0]);
        $this->assertStringEndsWith('; cd /srv/b/current && php-fpm reload', $hook[0]);
    }

    public function testFailingHookLeavesTheLeftReleaseInPlaceAndReleasesTheLock(): void
    {
        $this->paths = $this->project();

        $this->assertFalse($this->rollback([new Command('failing-hook')]));

        $this->assertArrayHasKey('/srv/review/releases/'.self::LIVE, $this->paths);
        $this->assertArrayNotHasKey(self::FAILED, $this->paths);
        $this->assertArrayNotHasKey(self::LOCK, $this->paths);
    }

    public function testExistingLockFileRefusesTheRollback(): void
    {
        $this->paths = $this->project() + [self::LOCK => true];

        $this->assertFalse($this->rollback());

        $this->assertSame([], $this->summary());
        $this->assertArrayHasKey(self::LOCK, $this->paths);
    }

    public function testForceIgnoresAnExistingLockFile(): void
    {
        $this->paths = $this->project() + [self::LOCK => true];

        $this->assertTrue($this->rollback(force: true));

        $this->assertSame('/srv/review/releases/'.self::OLD, $this->paths['/srv/review/current']);
        $this->assertArrayNotHasKey(self::LOCK, $this->paths);
    }

    /**
     * @param string[]                     $others  releases, besides the live one, of the single server
     * @param null|array<string, string[]> $servers other releases indexed by project path
     *
     * @return array<string, string|true>
     */
    private function project(array $others = [self::OLD], ?array $servers = null): array
    {
        $paths = [];
        foreach ($servers ?? ['/srv/review' => $others] as $path => $releases) {
            $paths += [$path => true, $path.'/releases' => true, $path.'/releases/'.self::LIVE => true, $path.'/current' => $path.'/releases/'.self::LIVE];
            foreach ($releases as $release) {
                $paths[$path.'/releases/'.$release] = true;
            }
        }

        return $paths;
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
            } elseif (1 === preg_match('/^(touch|rm|ln|mv) /', $command)) {
                $summary[] = $command;
            }
        }

        return $summary;
    }

    /**
     * The commands that change a release or "current".
     *
     * @return string[]
     */
    private function changes(): array
    {
        return array_values(array_filter($this->executed, static fn (string $command): bool => 1 === preg_match('/^(ln|mv|rm -R) /', $command)));
    }

    /**
     * @param Command[]             $hooks
     * @param array<string, string> $servers paths indexed by server name
     */
    private function rollback(array $hooks = [], ?string $release = null, bool $force = false, array $servers = ['vm' => '/srv/review']): bool
    {
        $platform = new Platform(name: 'review', defaultBranch: 'master');
        foreach ($servers as $name => $path) {
            $platform->addServer(new Server(name: $name, path: $path));
        }

        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('login');
        $ssh->shouldReceive('exec')->andReturnUsing($this->fakeServer(...));
        $sshFactory = \Mockery::mock(TransportFactory::class);
        $sshFactory->shouldReceive('create')->andReturns($ssh);

        $context = new Context((new Project())->setPostRollback($hooks), $platform, \Mockery::spy(LoggerInterface::class), $sshFactory, new Archiver(), force: $force, releaseId: '2024.03.12-0900.000', phase: 'rollback');

        return (new Rollbacker($context, $release))->rollback();
    }

    private function fakeServer(string $command): string
    {
        $this->executed[] = $command;

        if (1 === preg_match('/^if test -e "(.+)" \|\| test -L "\1"; then/', $command, $matches)) {
            return isset($this->paths[rtrim($matches[1], '/')]) ? 'Y' : '';
        }

        if (1 === preg_match('/^(?:mkdir -p|touch) (.+)$/', $command, $matches)) {
            $this->paths[$matches[1]] = true;
        } elseif (1 === preg_match('/^ln -sfn (\S+) (\S+)$/', $command, $matches)) {
            $this->paths[$matches[2]] = $matches[1];
        } elseif (1 === preg_match('/^rm(?: -R)? (.+)$/', $command, $matches)) {
            $removed = rtrim($matches[1], '/');
            unset($this->paths[$removed]);
            foreach (array_keys($this->paths) as $path) {
                if (str_starts_with($path, $removed.'/')) {
                    unset($this->paths[$path]);
                }
            }
        } elseif (1 === preg_match('/^mv (\S+) (\S+)$/', $command, $matches)) {
            // an existing destination folder nests the source inside it, as the real "mv" does
            $to = isset($this->paths[$matches[2]]) ? $matches[2].'/'.basename($matches[1]) : $matches[2];
            foreach ($this->paths as $path => $entry) {
                if ($path === $matches[1] || str_starts_with($path, $matches[1].'/')) {
                    unset($this->paths[$path]);
                    $this->paths[$to.substr($path, strlen($matches[1]))] = $entry;
                }
            }
        } elseif (1 === preg_match('/^readlink (\S+)/', $command, $matches)) {
            $entry = $this->paths[$matches[1]] ?? null;

            return is_string($entry) ? $entry."\n" : '';
        } elseif (1 === preg_match('/^find (\S+) -maxdepth 1 -mindepth 1 -type d$/', $command, $matches)) {
            $directories = array_filter(array_keys($this->paths), fn (string $path): bool => dirname($path) === $matches[1] && true === $this->paths[$path]);

            return implode("\n", $directories);
        } elseif (str_ends_with($command, 'failing-hook')) {
            throw new \RuntimeException('hook failed');
        }

        return '';
    }
}
