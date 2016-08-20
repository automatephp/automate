<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests;

use Automate\Session;
use phpseclib\Net\SSH2;
use Phake;

class SessionTest extends \PHPUnit_Framework_TestCase
{

    private $ssh;

    public function setUp()
    {
        $this->ssh = Phake::mock(SSH2::class);
    }

    public function testRun()
    {
        $session = new Session($this->ssh);

        $command = 'echo "test"';

        Phake::when($this->ssh)->exec($command)->thenReturn('test');
        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $rs = $session->run($command);

        $this->assertEquals('test', $rs);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRunWithError()
    {
        $session = new Session($this->ssh);

        $command = 'echo "test"';

        Phake::when($this->ssh)->exec($command)->thenReturn('test');
        Phake::when($this->ssh)->getExitStatus()->thenReturn(4);

        $rs = $session->run($command);
    }

    public function testMkdir()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->mkdir('/path/to/fomder');
        $session->mkdir('/path/to/fomder', true);

        Phake::inOrder(
            Phake::verify($this->ssh)->exec('mkdir /path/to/fomder'),
            Phake::verify($this->ssh)->exec('mkdir -p /path/to/fomder')
        );
    }

    public function testMv()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->mv('/home/a.txt', '/home/b.txt');

        Phake::verify($this->ssh)->exec('mv /home/a.txt /home/b.txt');
    }

    public function testMvWithMkdir()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->mv('/home/a', '/data/usr');

        Phake::inOrder(
            Phake::verify($this->ssh)->exec('mkdir -p /data'),
            Phake::verify($this->ssh)->exec('mv /home/a /data/usr')
        );
    }

    public function testRm()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->rm('/home/a.txt');

        Phake::verify($this->ssh)->exec('rm /home/a.txt');
    }

    public function testexistsFolder()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);
        Phake::when($this->ssh)->exec('if test -d "/home/test"; then echo "Y";fi')->thenReturn('Y');

        $this->assertTrue($session->exists('/home/test'));
    }

    public function testexistsFile()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);
        Phake::when($this->ssh)->exec('if test -f "/home/test.txt"; then echo "Y";fi')->thenReturn('Y');

        $this->assertTrue($session->exists('/home/test.txt'));
    }

    public function testSymlink()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->symlink('/data/a.txt', '/data/b.txt');

        Phake::verify($this->ssh)->exec('ln -sfn /data/a.txt /data/b.txt');
    }

    public function testTouch()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->touch('/data/a.txt');

        Phake::inOrder(
            Phake::verify($this->ssh)->exec('mkdir -p /data'),
            Phake::verify($this->ssh)->exec('touch /data/a.txt')
        );
    }

    public function testListDirectory()
    {
        $session = new Session($this->ssh);

        Phake::when($this->ssh)->getExitStatus()->thenReturn(0);

        $session->listDirectory('/data');

        Phake::verify($this->ssh)->exec('find /data -maxdepth 1 -mindepth 1 -type d');
    }

}