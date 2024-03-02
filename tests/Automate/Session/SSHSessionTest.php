<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Session;

use Automate\Session\SSHSession;
use Automate\Tests\AbstractMockTestCase;
use Mockery;
use phpseclib\Net\SSH2;

class SSHSessionTest extends AbstractMockTestCase
{
    private $ssh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ssh = Mockery::spy(SSH2::class);
    }

    public function testRun()
    {
        $session = new SSHSession($this->ssh);

        $command = 'echo "test"';

        $this->ssh->shouldReceive('exec')->with($command)->andReturns('test');
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $rs = $session->run($command);

        $this->assertEquals('test', $rs);
    }

    public function testRunWithError()
    {
        $session = new SSHSession($this->ssh);

        $command = 'echo "test"';

        $this->ssh->shouldReceive('exec')->with($command)->andReturns('test');
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(1);

        $this->expectException(\RuntimeException::class);

        $session->run($command);
    }

    public function testMkdir()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('mkdir /path/to/fomder')->once();
        $this->ssh->expects('exec')->with('mkdir -p /path/to/fomder')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->mkdir('/path/to/fomder');
        $session->mkdir('/path/to/fomder', true);
    }

    public function testMv()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('mv /home/a.txt /home/b.txt')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->mv('/home/a.txt', '/home/b.txt');
    }

    public function testMvWithMkdir()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('mkdir -p /data')->once();
        $this->ssh->expects('exec')->with('mv /home/a /data/usr')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->mv('/home/a', '/data/usr');
    }

    public function testRm()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('rm /home/a.txt')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->rm('/home/a.txt');
    }

    public function testFolderExists()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('if test -d "/home/test"; then echo "Y";fi')->once()->andReturns('Y');
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $this->assertTrue($session->exists('/home/test'));
    }

    public function testFileExists()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('if test -d "/home/test.txt"; then echo "Y";fi')->once()->andReturns('');
        $this->ssh->expects('exec')->with('if test -f "/home/test.txt"; then echo "Y";fi')->once()->andReturns('Y');
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $this->assertTrue($session->exists('/home/test.txt'));
    }

    public function testSymlink()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('ln -sfn /data/a.txt /data/b.txt')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->symlink('/data/a.txt', '/data/b.txt');
    }

    public function testTouch()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('mkdir -p /data')->once();
        $this->ssh->expects('exec')->with('touch /data/a.txt')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->touch('/data/a.txt');
    }

    public function testListDirectory()
    {
        $session = new SSHSession($this->ssh);

        $this->ssh->expects('exec')->with('find /data -maxdepth 1 -mindepth 1 -type d')->once();
        $this->ssh->shouldReceive()->getExitStatus()->andReturns(0);

        $session->listDirectory('/data');
    }
}
