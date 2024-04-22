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
    public function archive(string $path, array $exclude = []): \PharData
    {
        $this->clear($path);

        $archive = new \PharData($this->getArchiveFileName($path, false));
        $cwd = getcwd();

        if (is_dir($path)) {
            $finder = new Finder();
            $finder->files()
                ->in($path)
                ->notPath($exclude);

            $archive->buildFromIterator($finder->getIterator(), $cwd);
        } else {
            $archive->addFile($path, Path::makeRelative($path, $cwd));
        }

        /** @var \PharData $compressed */
        $compressed = $archive->compress(\Phar::GZ);

        return $compressed;
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
