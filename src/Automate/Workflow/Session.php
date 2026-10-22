<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Workflow;

use Automate\Model\Server;
use Automate\Ssh\Ssh;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

readonly class Session
{
    private const string SHARED_FOLDER = 'shared';

    private const string CURRENT_FOLDER = 'current';

    private const string RELEASES_FOLDER = 'releases';

    private const string RELEASE_ID_PATTERN = '/^\d{4}\.\d{2}\.\d{2}-\d{4}\.\d{3}$/';

    /**
     * @param array<string, string> $environment exported, on top of the release paths, to the commands run in the release directory
     */
    public function __construct(
        private Server $server,
        private Ssh $ssh,
        private string $releaseId,
        private array $environment = [],
    ) {
    }

    /**
     * Whether the folder name of this path is a release id. Only the name is
     * checked: the server path itself may contain a date-like segment.
     */
    public static function isReleaseId(string $path): bool
    {
        return 1 === preg_match(self::RELEASE_ID_PATTERN, basename(trim($path)));
    }

    public function login(): void
    {
        $this->ssh->login();
    }

    public function exec(string $command, bool $addWorkingDir = true): string
    {
        return $this->ssh->exec($this->buildCommand($command, $addWorkingDir));
    }

    public function execAsync(string $command, bool $addWorkingDir = true): Process
    {
        return $this->ssh->execAsync($this->buildCommand($command, $addWorkingDir));
    }

    private function buildCommand(string $command, bool $addWorkingDir): string
    {
        if (!$addWorkingDir) {
            return $command;
        }

        // "&&", not ";": should the release directory be missing, the command
        // must not run anyway from the home directory of the SSH user
        $command = sprintf('cd %s && %s', $this->getReleasePath(), $command);

        $environment = [
            'AUTOMATE_RELEASE_ID' => $this->releaseId,
            'AUTOMATE_RELEASE_PATH' => $this->getReleasePath(),
            'AUTOMATE_RELEASES_PATH' => $this->getReleasesPath(),
            'AUTOMATE_SHARED_PATH' => $this->getSharedPath(),
            'AUTOMATE_CURRENT_PATH' => $this->getCurrentPath(),
            ...$this->environment,
        ];

        $exports = [];
        foreach ($environment as $name => $value) {
            $exports[] = sprintf('%s=%s', $name, escapeshellarg($value));
        }

        return sprintf('export %s; %s', implode(' ', $exports), $command);
    }

    public function upload(string $path, string $target): void
    {
        $this->ssh->upload($path, $target);
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
