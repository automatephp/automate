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
        $this->clear($path);

        $archiveFile = $this->getArchiveFileName($path);
        $cwd = getcwd();

        // Build tar command
        $command = ['tar', '-czf', $archiveFile];

        // Add exclusions
        foreach ($exclude as $pattern) {
            $command[] = '--exclude='.$pattern;
        }

        // Add the path to archive (relative to cwd)
        $relativePath = Path::makeRelative($path, $cwd);
        $command[] = $relativePath;

        // Execute tar command
        $process = new Process($command, $cwd);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $archiveFile;
    }

    public function getArchiveFileName(string $path, bool $compressed = true): string
    {
        $name = u(self::BASE_ARCHIVE_NAME.$path)->snake();

        return $compressed ? $name.'.tar.gz' : $name.'.tar';
    }

    public function clear(string $path): void
    {
        $this->filesystem->remove($this->getArchiveFileName($path, false));
        $this->filesystem->remove($this->getArchiveFileName($path));
    }
}
