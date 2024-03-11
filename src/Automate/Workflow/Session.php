<?php

namespace Automate\Workflow;

use Automate\Model\Server;
use phpseclib3\Net\SFTP;
use Symfony\Component\Filesystem\Path;

readonly class Session
{
    private const string SHARED_FOLDER = 'shared';

    private const string CURRENT_FOLDER = 'current';

    private const string RELEASES_FOLDER = 'releases';

    public function __construct(
        private Server $server,
        private SFTP $sftp,
        private string $releaseId,
    ) {
        $this->sftp->setTimeout(0);
    }

    public function exec(string $command, bool $addWorkingDir = true): string
    {
        $command = $addWorkingDir ? sprintf('cd %s; %s', $this->getReleasePath(), $command) : $command;

        $rs = (string) $this->sftp->exec($command);

        if (0 !== $this->sftp->getExitStatus()) {
            throw new \RuntimeException($rs);
        }

        return $rs;
    }

    public function mkdir(string $path, bool $recursive = false): void
    {
        $command = sprintf('mkdir%s %s', $recursive ? ' -p' : '', $path);

        $this->exec($command, false);
    }

    public function mv(string $from, string $to): void
    {
        if (!$this->exists(dirname($to))) {
            $this->mkdir(dirname($to), true);
        }

        $this->exec(sprintf('mv %s %s', $from, $to), false);
    }

    public function rm(string $path, bool $recursive = false): void
    {
        $this->exec(sprintf('rm%s %s', $recursive ? ' -R' : '', $path), false);
    }

    public function exists(string $path): bool
    {
        if ('Y' === trim($this->exec(sprintf('if test -d "%s"; then echo "Y";fi', $path), false))) {
            return true;
        }

        return 'Y' === trim($this->exec(sprintf('if test -f "%s"; then echo "Y";fi', $path), false));
    }

    public function symlink(string $target, string $link): void
    {
        $this->exec(sprintf('ln -sfn %s %s', $target, $link), false);
    }

    public function touch(string $path): void
    {
        $this->mkdir(dirname($path), true);
        $this->exec(sprintf('touch %s', $path), false);
    }

    /**
     * @return string[]
     */
    public function listDirectory(string $path): array
    {
        $rs = $this->exec(sprintf('find %s -maxdepth 1 -mindepth 1 -type d', $path), false);

        return explode("\n", trim($rs));
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getReleasePath(): string
    {
        return Path::join($this->getReleasesPath(), $this->releaseId);
    }

    public function getReleasesPath(): string
    {
        return Path::join($this->server->getPath(), self::RELEASES_FOLDER);
    }

    public function getSharedPath(): string
    {
        $serverSharedPath = $this->server->getSharedPath();

        // if the shared path is not configured on the server configuration
        if (null === $serverSharedPath) {
            $serverSharedPath = Path::join($this->server->getPath(), self::SHARED_FOLDER);
        }

        return $serverSharedPath;
    }

    public function getCurrentPath(): string
    {
        return Path::join($this->server->getPath(), self::CURRENT_FOLDER);
    }
}
