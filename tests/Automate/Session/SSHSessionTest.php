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
use phpseclib\Net\SSH2;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SSHSessionTest extends TestCase
{
    private $ssh;

    protected function setUp() :void
    {
        $this->ssh = $this->prophesize(SSH2::class);
        $this->ssh->setTimeout(0)->shouldBeCalled();
    }

    public function testRun()
    {
        $session = new SSHSession($this->ssh->reveal());

        $command = 'echo "test"';

        $this->ssh->exec($command)->willReturn('test');
        $this->ssh->getExitStatus()->willReturn(0);

        $rs = $session->run($command);

        $this->assertEquals('test', $rs);
    }

    public function testRunWithError()
    {
        $session = new SSHSession($this->ssh->reveal());

        $command = 'echo "test"';

        $this->ssh->exec($command)->willReturn('test');
        $this->ssh->getExitStatus()->willReturn(4);

        $this->expectException(\RuntimeException::class);

        $rs = $session->run($command);
    }

    public function testMkdir()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->exec('mkdir /path/to/fomder')->shouldBeCalled();
        $this->ssh->exec('mkdir -p /path/to/fomder')->shouldBeCalled();
        $this->ssh->getExitStatus()->willReturn(0);


        $session->mkdir('/path/to/fomder');
        $session->mkdir('/path/to/fomder', true);

    }

    public function testMv()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec(Argument::any())->shouldBeCalled();
        $this->ssh->exec('mv /home/a.txt /home/b.txt')->shouldBeCalled();

        $session->mv('/home/a.txt', '/home/b.txt');
    }

    public function testMvWithMkdir()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec(Argument::any())->shouldBeCalled();
        $this->ssh->exec('mkdir -p /data')->shouldBeCalled();
        $this->ssh->exec('mv /home/a /data/usr')->shouldBeCalled();

        $session->mv('/home/a', '/data/usr');
    }

    public function testRm()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec('rm /home/a.txt')->shouldBeCalled();

        $session->rm('/home/a.txt');
    }

    public function testexistsFolder()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec('if test -d "/home/test"; then echo "Y";fi')->willReturn('Y');

        $this->assertTrue($session->exists('/home/test'));
    }

    public function testexistsFile()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec('if test -d "/home/test.txt"; then echo "Y";fi')->willReturn(null);
        $this->ssh->exec('if test -f "/home/test.txt"; then echo "Y";fi')->willReturn('Y');

        $this->assertTrue($session->exists('/home/test.txt'));
    }

    public function testSymlink()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec('ln -sfn /data/a.txt /data/b.txt')->shouldBeCalled();

        $session->symlink('/data/a.txt', '/data/b.txt');
    }

    public function testTouch()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);

        $session->touch('/data/a.txt');

        $this->ssh->exec('mkdir -p /data')->shouldBeCalled();
        $this->ssh->exec('touch /data/a.txt')->shouldBeCalled();

    }

    public function testListDirectory()
    {
        $session = new SSHSession($this->ssh->reveal());

        $this->ssh->getExitStatus()->willReturn(0);
        $this->ssh->exec('find /data -maxdepth 1 -mindepth 1 -type d')->shouldBeCalled();

        $session->listDirectory('/data');
    }
}
