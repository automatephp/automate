<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests\Transport;

use Automate\Transport\LocalTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LocalTransportTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/automate-local-'.bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testExecReturnsTheOutput(): void
    {
        $this->assertSame("hello\n", (new LocalTransport())->exec('echo hello'));
    }

    public function testExecIncludesTheErrorOutput(): void
    {
        $this->assertStringContainsString('oops', (new LocalTransport())->exec('echo oops >&2'));
    }

    public function testExecFailureThrowsTheOutput(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        (new LocalTransport())->exec('echo boom >&2; exit 3');
    }

    public function testExecRunsShellCommandLines(): void
    {
        $this->assertSame("a\n", (new LocalTransport())->exec(sprintf('cd %s && echo a | cat', $this->directory)));
    }

    public function testUploadCopiesTheFile(): void
    {
        file_put_contents($this->directory.'/source.txt', 'content');

        (new LocalTransport())->upload($this->directory.'/source.txt', $this->directory.'/target.txt');

        $this->assertSame('content', file_get_contents($this->directory.'/target.txt'));
    }

    public function testUploadOverwritesTheTarget(): void
    {
        file_put_contents($this->directory.'/source.txt', 'new');
        file_put_contents($this->directory.'/target.txt', 'old');

        (new LocalTransport())->upload($this->directory.'/source.txt', $this->directory.'/target.txt');

        $this->assertSame('new', file_get_contents($this->directory.'/target.txt'));
    }

    public function testExecAsyncReturnsAProcessToRun(): void
    {
        $process = (new LocalTransport())->execAsync('echo async');
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertSame("async\n", $process->getOutput());
    }

    public function testExecAsyncReportsTheFailure(): void
    {
        $process = (new LocalTransport())->execAsync('exit 2');
        $process->run();

        $this->assertFalse($process->isSuccessful());
    }
}
