<?php

namespace Automate\Tests\Workflow;

use Automate\Model\Server;
use Automate\Tests\AbstractMockTestCase;
use Automate\Transport\TransportInterface;
use Automate\Workflow\Session;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

class SessionTest extends AbstractMockTestCase
{
    private const string RELEASE_EXPORTS = "export AUTOMATE_RELEASE_ID='2024.03.10-2340.241' AUTOMATE_RELEASE_PATH='/var/www/releases/2024.03.10-2340.241'"
        ." AUTOMATE_RELEASES_PATH='/var/www/releases' AUTOMATE_SHARED_PATH='/var/www/shared' AUTOMATE_CURRENT_PATH='/var/www/current'";

    public function testSessionPath(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $session = $this->getSession($ssh);

        $this->assertEquals('/var/www/current', $session->getCurrentPath());
        $this->assertEquals('/var/www/shared', $session->getSharedPath());
        $this->assertEquals('/var/www/releases', $session->getReleasesPath());
        $this->assertEquals('/var/www/releases/2024.03.10-2340.241', $session->getReleasePath());
    }

    public function testExecCommand(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('ls');

        $session = $this->getSession($ssh);

        $session->exec('ls', false);
    }

    /**
     * The commands are chained with "&&": a missing release directory must
     * abort the command, not run it from the home directory of the SSH user.
     */
    public function testExecCommandWithWorkingDir(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with(self::RELEASE_EXPORTS." AUTOMATE_FIRST_DEPLOY='0'; cd /var/www/releases/2024.03.10-2340.241 && ls")->once();

        $session = $this->getSession($ssh);

        $session->exec('ls');
    }

    public function testExecCommandExportsTheEnvironment(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with(
            self::RELEASE_EXPORTS." AUTOMATE_FIRST_DEPLOY='0' AUTOMATE_PLATFORM='review' AUTOMATE__slug='feat-x'; cd /var/www/releases/2024.03.10-2340.241 && ls"
        )->once();

        $session = $this->getSession($ssh, ['AUTOMATE_PLATFORM' => 'review', 'AUTOMATE__slug' => 'feat-x']);

        $session->exec('ls');
    }

    public function testExecCommandEscapesSingleQuotesInTheEnvironment(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with(\Mockery::on(static fn (string $command): bool => str_contains($command, "AUTOMATE__x='a'\\''; rm -rf /; echo '\\''b'")))->once();

        $session = $this->getSession($ssh, ['AUTOMATE__x' => "a'; rm -rf /; echo 'b"]);

        $session->exec('ls');
    }

    public function testInternalCommandsDoNotExportTheEnvironment(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('ls')->once();

        $session = $this->getSession($ssh, ['AUTOMATE_PLATFORM' => 'review']);

        $session->exec('ls', false);
    }

    public function testExecAsyncExportsTheEnvironment(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('execAsync')->with(
            self::RELEASE_EXPORTS." AUTOMATE_FIRST_DEPLOY='0' AUTOMATE_PLATFORM='review'; cd /var/www/releases/2024.03.10-2340.241 && ls"
        )->once()->andReturns(new Process(['true']));

        $session = $this->getSession($ssh, ['AUTOMATE_PLATFORM' => 'review']);

        $session->execAsync('ls');
    }

    public function testExecCommandInAnotherWorkingDir(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with(self::RELEASE_EXPORTS." AUTOMATE_FIRST_DEPLOY='0'; cd /var/www/current && ls")->once();

        $this->getSession($ssh)->exec('ls', workingDir: '/var/www/current');
    }

    public function testWithReleaseIdChangesTheReleasePathAndKeepsTheEnvironment(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with(\Mockery::on(static fn (string $command): bool => str_contains($command, "AUTOMATE_RELEASE_ID='2024.01.01-0000.001'") && str_contains($command, "AUTOMATE_PLATFORM='review'")))->once();

        $session = $this->getSession($ssh, ['AUTOMATE_PLATFORM' => 'review'])->withReleaseId('2024.01.01-0000.001');

        $this->assertEquals('/var/www/releases/2024.01.01-0000.001', $session->getReleasePath());
        $session->exec('ls');
    }

    public function testWithCurrentReleaseFollowsTheCurrentSymlink(): void
    {
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('readlink /var/www/current || true')->once()->andReturns("/var/www/releases/2024.01.01-0000.001\n");

        $session = $this->getSession($ssh)->withCurrentRelease();

        $this->assertEquals('/var/www/releases/2024.01.01-0000.001', $session->getReleasePath());
    }

    public function testWithCurrentReleaseIsUnchangedWithoutSymlink(): void
    {
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('readlink /var/www/current || true')->once()->andReturns('');

        $this->assertEquals('/var/www/releases/2024.03.10-2340.241', $this->getSession($ssh)->withCurrentRelease()->getReleasePath());
    }

    public function testMkdir(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('mkdir /path/to/folder')->once();
        $ssh->expects('exec')->with('mkdir -p /path/to/folder')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->mkdir('/path/to/folder');
        $session->mkdir('/path/to/folder', true);
    }

    public function testMv(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('mv /home/a.txt /home/b.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->mv('/home/a.txt', '/home/b.txt');
    }

    public function testMvWithMkdir(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('mkdir -p /data');
        $ssh->expects('exec')->with('mv /home/a /data/usr')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->mv('/home/a', '/data/usr');
    }

    public function testRm(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('rm /home/a.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->rm('/home/a.txt');
    }

    /**
     * A single round trip that also sees a dangling symlink.
     */
    public function testExists(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('if test -e "/home/test" || test -L "/home/test"; then echo "Y";fi')->once()->andReturns("Y\n");
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $this->assertTrue($session->exists('/home/test'));
    }

    public function testDoesNotExist(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('if test -e "/home/missing" || test -L "/home/missing"; then echo "Y";fi')->once()->andReturns('');
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $this->assertFalse($session->exists('/home/missing'));
    }

    public function testSymlink(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('ln -sfn /data/a.txt /data/b.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->symlink('/data/a.txt', '/data/b.txt');
    }

    public function testTouch(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('mkdir -p /data')->once();
        $ssh->expects('exec')->with('touch /data/a.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->touch('/data/a.txt');
    }

    public function testListDirectory(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with('find /data -maxdepth 1 -mindepth 1 -type d')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->listDirectory('/data');
    }

    public function testHasReleasesIsFalseWithoutReleasesFolder(): void
    {
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('if test -e "/var/www/releases" || test -L "/var/www/releases"; then echo "Y";fi')->once()->andReturns('');

        $this->assertFalse($this->getSession($ssh)->hasReleases());
    }

    public function testHasReleasesIgnoresTheFailedRelease(): void
    {
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('if test -e "/var/www/releases" || test -L "/var/www/releases"; then echo "Y";fi')->andReturns('Y');
        $ssh->shouldReceive('exec')->with('find /var/www/releases -maxdepth 1 -mindepth 1 -type d')->andReturns('/var/www/releases/failed');

        $this->assertFalse($this->getSession($ssh)->hasReleases());
    }

    public function testHasReleasesWithASuccessfulRelease(): void
    {
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('if test -e "/var/www/releases" || test -L "/var/www/releases"; then echo "Y";fi')->andReturns('Y');
        $ssh->shouldReceive('exec')->with('find /var/www/releases -maxdepth 1 -mindepth 1 -type d')->andReturns("/var/www/releases/failed\n/var/www/releases/2024.03.09-1200.123");

        $this->assertTrue($this->getSession($ssh)->hasReleases());
    }

    /**
     * A failed release under a server path that looks like a release id must
     * not count as a release.
     */
    public function testHasReleasesIgnoresTheFailedReleaseWhenThePathLooksLikeARelease(): void
    {
        $ssh = \Mockery::mock(TransportInterface::class);
        $ssh->shouldReceive('exec')->with('if test -e "/srv/2024.01.01-1200.123/app/releases" || test -L "/srv/2024.01.01-1200.123/app/releases"; then echo "Y";fi')->andReturns('Y');
        $ssh->shouldReceive('exec')->with('find /srv/2024.01.01-1200.123/app/releases -maxdepth 1 -mindepth 1 -type d')->andReturns('/srv/2024.01.01-1200.123/app/releases/failed');

        $this->assertFalse($this->getSession($ssh, path: '/srv/2024.01.01-1200.123/app')->hasReleases());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function releaseIdProvider(): iterable
    {
        yield 'release id' => ['2024.03.09-1200.123', true];
        yield 'full path' => ['/var/www/releases/2024.03.09-1200.123', true];
        yield 'trailing new line' => ["/var/www/releases/2024.03.09-1200.123\n", true];
        yield 'failed' => ['failed', false];
        yield 'prefixed' => ['failed-2024.03.09-1200.123', false];
        yield 'suffixed' => ['2024.03.09-1200.123.bak', false];
        yield 'date-like parent only' => ['/srv/2024.01.01-1200.123/app/releases/failed', false];
    }

    #[DataProvider('releaseIdProvider')]
    public function testIsReleaseId(string $path, bool $expected): void
    {
        $this->assertSame($expected, Session::isReleaseId($path));
    }

    public function testFirstDeploymentIsExportedToTheHooks(): void
    {
        $ssh = \Mockery::spy(TransportInterface::class);
        $ssh->expects('exec')->with(\Mockery::on(static fn (string $command): bool => str_contains($command, "AUTOMATE_FIRST_DEPLOY='1'")))->once();

        $session = $this->getSession($ssh, ['AUTOMATE_PLATFORM' => 'review'])->withFirstDeploy(true);

        $this->assertTrue($session->isFirstDeploy());
        $session->exec('ls');
    }

    /**
     * @param array<string, string> $environment
     */
    private function getSession(TransportInterface $ssh, array $environment = [], string $path = '/var/www'): Session
    {
        $server = new Server(name : 'server1', path: $path);

        return new Session($server, $ssh, '2024.03.10-2340.241', $environment);
    }
}
