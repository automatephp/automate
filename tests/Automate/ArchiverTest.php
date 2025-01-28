<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use PHPUnit\Framework\TestCase;

class ArchiverTest extends TestCase
{
    private const string PATH = __DIR__.'/../fixtures/folder';

    private Archiver $archiver;

    protected function setUp(): void
    {
        $this->archiver = new Archiver();
    }

    protected function tearDown(): void
    {
        $this->archiver->clear(self::PATH);
    }

    public function testArchive(): void
    {
        $phar = $this->archiver->archive(self::PATH);

        $this->assertTrue($phar->offsetExists('tests/fixtures/folder/a.txt'));
        $this->assertTrue($phar->offsetExists('tests/fixtures/folder/sub/b.txt'));
        $this->assertTrue($phar->offsetExists('tests/fixtures/folder/.sub/c.txt'));
        $this->assertTrue($phar->offsetExists('tests/fixtures/folder/.d.txt'));
    }
}
