<?php


namespace Automate;


use phpseclib\Net\SSH2;

class Session
{
    /**
     * @var SSH2
     */
    private $ssh;

    /**
     * Session constructor.
     *
     * @param SSH2 $ssh
     */
    public function __construct(SSH2 $ssh)
    {
        $this->ssh = $ssh;
    }


    /**
     * Execute e command
     *
     * @param  string  $command
     *
     * @return mixed
     */
    public function run($command)
    {
        $rs = $this->ssh->exec($command);

        if(0 !== $this->ssh->getExitStatus()) {
            throw new \RuntimeException($rs);
        }

        return $rs;
    }

    /**
     * Creates a directory
     *
     * @param  string  $path      The name of the new directory
     * @param  boolean $recursive Whether to automatically create any required
     *                            parent directory
     *
     */
    public function mkdir($path, $recursive = false)
    {
        $command = sprintf('mkdir%s %s', $recursive ? ' -p' : '', $path);

        $this->run($command);
    }

    /**
     * Move a file or a directory
     *
     * @param  string  $from    The current name of the directory or file
     * @param  string  $to      The new name of the directory or file
     */
    public function mv($from, $to)
    {
        if(!$this->exists(dirname($to))) {
            $this->run(sprintf('mkdir -p %s', dirname($to)));
        }

        $this->run(sprintf('mv %s %s', $from, $to));
    }

    /**
     * Removes a directory or a file
     *
     * @param  string  $path The directory or file that is being removed
     * @param  boolean $recursive
     */
    public function rm($path, $recursive = false)
    {
        $this->run(sprintf('rm%s %s', $recursive ? ' -R' : '', $path));
    }

    /**
     * Indicates whether the specified distant file or directory exists
     *
     * @param  string $path The distant filename ou directory
     *
     * @return boolean
     */
    public function exists($path)
    {
        if('Y' === trim($this->run(sprintf('if test -d "%s"; then echo "Y";fi', $path)))) {
            return true;
        }

        if('Y' === trim($this->run(sprintf('if test -f "%s"; then echo "Y";fi', $path)))) {
            return true;
        }

        return false;
    }

    /**
     * Creates a symlink
     *
     * @param  string $target The target of the symlink
     * @param  string $link   The path of the link
     */
    public function symlink($target, $link)
    {
        $this->run(sprintf('ln -sfn %s %s', $target, $link));
    }


    /**
     * Touch file
     *
     * @param  string $path FIle path
     */
    public function touch($path)
    {
        $this->run(sprintf('mkdir -p %s', dirname($path)));
        $this->run(sprintf('touch %s', $path));
    }




    /**
     * Lists directories of the specified path
     *
     * @param  string $path
     *
     * @return array
     */
    public function listDirectory($path)
    {
        $rs = $this->run(sprintf('find %s -maxdepth 1 -mindepth 1 -type d', $path));

        return explode("\n", trim($rs));
    }

}