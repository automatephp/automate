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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

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
        $archiveFile = $this->archiver->archive(self::PATH);

        // Verify the archive file was created
        $this->assertFileExists($archiveFile);

        // Open and verify archive contents
        $members = $this->getArchiveMembers($archiveFile);
        $this->assertContains('tests/fixtures/folder/a.txt', $members);
        $this->assertContains('tests/fixtures/folder/sub/b.txt', $members);
        $this->assertContains('tests/fixtures/folder/.sub/c.txt', $members);
        $this->assertContains('tests/fixtures/folder/.d.txt', $members);
    }

    /**
     * @return iterable<string, array{string[], string[]}>
     */
    public static function excludeProvider(): iterable
    {
        yield 'a folder name' => [['sub'], ['tests/fixtures/folder/sub/b.txt']];
        yield 'a file name' => [['a.txt'], ['tests/fixtures/folder/a.txt']];
        yield 'a dot folder' => [['.sub'], ['tests/fixtures/folder/.sub/c.txt']];
        yield 'an anchored regexp' => [['/^sub/'], ['tests/fixtures/folder/sub/b.txt']];
        yield 'several patterns' => [
            ['sub', 'a.txt'],
            ['tests/fixtures/folder/sub/b.txt', 'tests/fixtures/folder/a.txt'],
        ];
    }

    /**
     * @param string[] $exclude
     * @param string[] $excludedMembers
     */
    #[DataProvider('excludeProvider')]
    public function testArchiveExclude(array $exclude, array $excludedMembers): void
    {
        $archiveFile = $this->archiver->archive(self::PATH, $exclude);

        $members = $this->getArchiveMembers($archiveFile);

        foreach ($excludedMembers as $member) {
            $this->assertNotContains($member, $members, sprintf('"%s" should have been excluded.', $member));
        }

        // the rest of the folder is still there
        $this->assertContains('tests/fixtures/folder/.d.txt', $members);
    }

    public function testArchiveKeepsDirectoriesWithoutRecursingIntoExcludedOnes(): void
    {
        $empty = self::PATH.'/empty';
        $link = self::PATH.'/link';
        mkdir($empty);
        symlink('sub', $link);

        try {
            $members = $this->getArchiveMembers($this->archiver->archive(self::PATH, ['sub/b.txt']));
        } finally {
            rmdir($empty);
            unlink($link);
        }

        $this->assertContains('tests/fixtures/folder/empty/', $members);
        $this->assertContains('tests/fixtures/folder/link', $members);
        $this->assertContains('tests/fixtures/folder/sub/', $members);
        $this->assertNotContains('tests/fixtures/folder/sub/b.txt', $members);
    }

    public function testArchiveRejectsInvalidExcludePattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid "exclude" pattern "/^folder/subfolder/"');

        $this->archiver->archive(self::PATH, ['/^folder/subfolder/']);
    }

    public function testArchiveAcceptsPlainStringsContainingSlashes(): void
    {
        $members = $this->getArchiveMembers($this->archiver->archive(self::PATH, ['sub/b.txt']));

        $this->assertNotContains('tests/fixtures/folder/sub/b.txt', $members);
    }

    /**
     * PharData caches archives by file name, and every archive built here shares
     * the same one: read the members with tar instead.
     *
     * @return string[]
     */
    private function getArchiveMembers(string $archiveFile): array
    {
        $process = new Process(['tar', '-tzf', $archiveFile]);
        $process->mustRun();

        return array_values(array_filter(explode("\n", trim($process->getOutput()))));
    }
}
