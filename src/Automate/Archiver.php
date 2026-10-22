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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function Symfony\Component\String\u;

class Archiver
{
    private const string BASE_ARCHIVE_NAME = '.automate-';

    private readonly Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string[] $exclude
     */
    public function archive(string $path, array $exclude = []): string
    {
        $this->assertValidExcludes($exclude);
        $this->clear($path);

        $archiveFile = $this->getArchiveFileName($path);
        $cwd = getcwd();

        // Create archive in temp directory to avoid "file changed as we read it" error
        $tempArchive = sys_get_temp_dir().'/'.basename($archiveFile);

        // Build tar command. The members are listed explicitly, NUL separated, so
        // that "exclude" keeps the Finder semantics documented in the reference:
        // tar's own --exclude only understands globs and would silently ignore a
        // pattern such as "/^myfile.ext/".
        // --no-recursion: directories are listed too (empty ones, symlinks), and tar
        // must not walk into them and re-add the excluded files.
        $command = ['tar', '-czf', $tempArchive, '--null', '--no-recursion', '--files-from=-'];

        // Execute tar command
        $process = new Process($command, $cwd, input: $this->getMemberList($path, $exclude, $cwd));
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->filesystem->rename($tempArchive, $archiveFile, true);

        return $archiveFile;
    }

    /**
     * Build the NUL separated list of files tar has to archive, relative to the
     * current working directory.
     *
     * @param string[] $exclude
     */
    private function getMemberList(string $path, array $exclude, string $cwd): string
    {
        // use a relative path to avoid absolute path warnings
        $relativePath = Path::makeRelative($path, $cwd) ?: '.';

        if (!is_dir($path)) {
            return $relativePath."\0";
        }

        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->in($path);

        if ([] !== $exclude) {
            $finder->notPath($exclude);
        }

        $list = $relativePath."\0";
        foreach ($finder as $file) {
            $list .= Path::join($relativePath, $file->getRelativePathname())."\0";
        }

        return $list;
    }

    /**
     * Finder silently treats an unparsable regular expression as a plain string and
     * warns on every file: fail upfront instead.
     *
     * @param string[] $exclude
     */
    private function assertValidExcludes(array $exclude): void
    {
        foreach ($exclude as $pattern) {
            $looksLikeRegex = 1 === preg_match('/^([^\w\s*?\\\\]).+\1[imsxuADUn]*$/s', $pattern);

            if ($looksLikeRegex && false === @preg_match($pattern, '')) {
                throw new \InvalidArgumentException(sprintf('Invalid "exclude" pattern "%s": %s. Escape the delimiter inside the expression, e.g. "/^folder\/subfolder/".', $pattern, preg_last_error_msg()));
            }
        }
    }

    public function getArchiveFileName(string $path): string
    {
        return u(self::BASE_ARCHIVE_NAME.$path)->snake().'.tar.gz';
    }

    public function clear(string $path): void
    {
        $this->filesystem->remove($this->getArchiveFileName($path));
    }
}
