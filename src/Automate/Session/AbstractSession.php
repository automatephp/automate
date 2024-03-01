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
    /**
     * {@inheritdoc}
     */
    abstract public function run($command);

    /**
     * {@inheritdoc}
     */
    public function mkdir($path, $recursive = false)
    {
        $command = sprintf('mkdir%s %s', $recursive ? ' -p' : '', $path);

        $this->run($command);
    }

    /**
     * {@inheritdoc}
     */
    public function mv($from, $to)
    {
        if (!$this->exists(dirname($to))) {
            $this->run(sprintf('mkdir -p %s', dirname($to)));
        }

        $this->run(sprintf('mv %s %s', $from, $to));
    }

    /**
     * {@inheritdoc}
     */
    public function rm($path, $recursive = false)
    {
        $this->run(sprintf('rm%s %s', $recursive ? ' -R' : '', $path));
    }

    /**
     * {@inheritdoc}
     */
    public function exists($path)
    {
        if ('Y' === trim($this->run(sprintf('if test -d "%s"; then echo "Y";fi', $path)))) {
            return true;
        }

        if ('Y' === trim($this->run(sprintf('if test -f "%s"; then echo "Y";fi', $path)))) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function symlink($target, $link)
    {
        $this->run(sprintf('ln -sfn %s %s', $target, $link));
    }

    /**
     * {@inheritdoc}
     */
    public function touch($path)
    {
        $this->run(sprintf('mkdir -p %s', dirname($path)));
        $this->run(sprintf('touch %s', $path));
    }

    /**
     * {@inheritdoc}
     */
    public function listDirectory($path)
    {
        $rs = $this->run(sprintf('find %s -maxdepth 1 -mindepth 1 -type d', $path));

        return explode("\n", trim($rs));
    }
}
