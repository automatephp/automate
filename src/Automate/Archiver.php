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

        // Create archive in temp directory to avoid "file changed as we read it" error
        $tempArchive = sys_get_temp_dir().'/'.basename($archiveFile);

        // Build tar command
        $command = ['tar', '-czf', $tempArchive];

        // Add exclusions
        foreach ($exclude as $pattern) {
            $command[] = '--exclude='.$pattern;
        }

        // Add the path to archive (use relative path to avoid absolute path warnings)
        $relativePath = Path::makeRelative($path, $cwd);
        $command[] = $relativePath ?: '.';

        // Execute tar command
        $process = new Process($command, $cwd);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->filesystem->rename($tempArchive, $archiveFile, true);

        return $archiveFile;
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
