<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Session;

abstract class AbstractSession implements SessionInterface
{
    abstract public function run($command);

    public function mkdir($path, $recursive = false)
    {
        $command = sprintf('mkdir%s %s', $recursive ? ' -p' : '', $path);

        $this->run($command);
    }

    public function mv($from, $to)
    {
        if (!$this->exists(dirname($to))) {
            $this->run(sprintf('mkdir -p %s', dirname($to)));
        }

        $this->run(sprintf('mv %s %s', $from, $to));
    }

    public function rm($path, $recursive = false)
    {
        $this->run(sprintf('rm%s %s', $recursive ? ' -R' : '', $path));
    }

    public function exists($path)
    {
        if ('Y' === trim((string) $this->run(sprintf('if test -d "%s"; then echo "Y";fi', $path)))) {
            return true;
        }

        return 'Y' === trim((string) $this->run(sprintf('if test -f "%s"; then echo "Y";fi', $path)));
    }

    public function symlink($target, $link)
    {
        $this->run(sprintf('ln -sfn %s %s', $target, $link));
    }

    public function touch($path)
    {
        $this->run(sprintf('mkdir -p %s', dirname($path)));
        $this->run(sprintf('touch %s', $path));
    }

    public function listDirectory($path)
    {
        $rs = (string) $this->run(sprintf('find %s -maxdepth 1 -mindepth 1 -type d', $path));

        return explode("\n", trim($rs));
    }
}
