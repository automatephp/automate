<?php

namespace Automate\Tests\Workflow;

use Automate\Model\Server;
use Automate\Ssh\Ssh;
use Automate\Tests\AbstractMockTestCase;
use Automate\Workflow\Session;

class SessionTest extends AbstractMockTestCase
{
    public function testSessionPath(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $session = $this->getSession($ssh);

        $this->assertEquals('/var/www/current', $session->getCurrentPath());
        $this->assertEquals('/var/www/shared', $session->getSharedPath());
        $this->assertEquals('/var/www/releases', $session->getReleasesPath());
        $this->assertEquals('/var/www/releases/2024.03.10-2340.241', $session->getReleasePath());
    }

    public function testExecCommand(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('ls');

        $session = $this->getSession($ssh);

        $session->exec('ls', false);
    }

    public function testExecCommandWithWorkingDir(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('cd /var/www/releases/2024.03.10-2340.241; ls');

        $session = $this->getSession($ssh);

        $session->exec('ls');
    }

    public function testMkdir(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('mkdir /path/to/folder')->once();
        $ssh->expects('exec')->with('mkdir -p /path/to/folder')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->mkdir('/path/to/folder');
        $session->mkdir('/path/to/folder', true);
    }

    public function testMv(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('mv /home/a.txt /home/b.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->mv('/home/a.txt', '/home/b.txt');
    }

    public function testMvWithMkdir(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('mkdir -p /data');
        $ssh->expects('exec')->with('mv /home/a /data/usr')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->mv('/home/a', '/data/usr');
    }

    public function testRm(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('rm /home/a.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->rm('/home/a.txt');
    }

    public function testFolderExists(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('if test -d "/home/test"; then echo "Y";fi')->once()->andReturns('Y');
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $this->assertTrue($session->exists('/home/test'));
    }

    public function testFileExists(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('if test -d "/home/test.txt"; then echo "Y";fi')->once()->andReturns('');
        $ssh->expects('exec')->with('if test -f "/home/test.txt"; then echo "Y";fi')->once()->andReturns('Y');
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $this->assertTrue($session->exists('/home/test.txt'));
    }

    public function testSymlink(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('ln -sfn /data/a.txt /data/b.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->symlink('/data/a.txt', '/data/b.txt');
    }

    public function testTouch(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('mkdir -p /data')->once();
        $ssh->expects('exec')->with('touch /data/a.txt')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->touch('/data/a.txt');
    }

    public function testListDirectory(): void
    {
        $ssh = \Mockery::spy(Ssh::class);
        $ssh->expects('exec')->with('find /data -maxdepth 1 -mindepth 1 -type d')->once();
        $ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session = $this->getSession($ssh);

        $session->listDirectory('/data');
    }

    private function getSession(Ssh $ssh): Session
    {
        $server = new Server(name : 'server1', path: '/var/www');

        return new Session($server, $ssh, '2024.03.10-2340.241');
    }
}
