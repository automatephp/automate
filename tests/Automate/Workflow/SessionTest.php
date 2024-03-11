<?php

namespace Automate\Tests\Workflow;

use Automate\Model\Server;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Session;
use phpseclib3\Net\SFTP;

class SessionTest extends AbstractMockTestCase
{
    public function testSessionPath(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $session = $this->getSession($sftp);

        $this->assertEquals('/var/www/current', $session->getCurrentPath());
        $this->assertEquals('/var/www/shared', $session->getSharedPath());
        $this->assertEquals('/var/www/releases', $session->getReleasesPath());
        $this->assertEquals('/var/www/releases/2024.03.10-2340.241', $session->getReleasePath());
    }

    public function testExecCommand(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('ls');
        $sftp->expects('getExitStatus')->andReturns(0);

        $session = $this->getSession($sftp);

        $session->exec('ls', false);
    }

    public function testExecCommandWithWorkingDir(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('cd /var/www/releases/2024.03.10-2340.241; ls');
        $sftp->expects('getExitStatus')->andReturns(0);

        $session = $this->getSession($sftp);

        $session->exec('ls');
    }

    public function testExecCommandWithError(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('getExitStatus')->andReturns(1);

        $session = $this->getSession($sftp);

        $this->expectException(\RuntimeException::class);

        $session->exec('ls', false);
    }

    public function testMkdir(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('mkdir /path/to/folder')->once();
        $sftp->expects('exec')->with('mkdir -p /path/to/folder')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->mkdir('/path/to/folder');
        $session->mkdir('/path/to/folder', true);
    }

    public function testMv(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('mv /home/a.txt /home/b.txt')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->mv('/home/a.txt', '/home/b.txt');
    }

    public function testMvWithMkdir(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('mkdir -p /data');
        $sftp->expects('exec')->with('mv /home/a /data/usr')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->mv('/home/a', '/data/usr');
    }

    public function testRm(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('rm /home/a.txt')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->rm('/home/a.txt');
    }

    public function testFolderExists(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('if test -d "/home/test"; then echo "Y";fi')->once()->andReturns('Y');
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $this->assertTrue($session->exists('/home/test'));
    }

    public function testFileExists(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('if test -d "/home/test.txt"; then echo "Y";fi')->once()->andReturns('');
        $sftp->expects('exec')->with('if test -f "/home/test.txt"; then echo "Y";fi')->once()->andReturns('Y');
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $this->assertTrue($session->exists('/home/test.txt'));
    }

    public function testSymlink(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('ln -sfn /data/a.txt /data/b.txt')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->symlink('/data/a.txt', '/data/b.txt');
    }

    public function testTouch(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('mkdir -p /data')->once();
        $sftp->expects('exec')->with('touch /data/a.txt')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->touch('/data/a.txt');
    }

    public function testListDirectory(): void
    {
        $sftp = \Mockery::spy(SFTP::class);
        $sftp->expects('exec')->with('find /data -maxdepth 1 -mindepth 1 -type d')->once();
        $sftp->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($sftp);

        $session->listDirectory('/data');
    }

    private function getSession(SFTP $sftp): Session
    {
        $server = new Server(name : 'server1', path: '/var/www');

        return new Session($server, $sftp, '2024.03.10-2340.241');
    }
}
