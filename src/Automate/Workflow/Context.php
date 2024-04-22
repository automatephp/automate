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

use Automate\Archiver;
use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Ssh\SshFactory;
use Symfony\Component\Filesystem\Path;

class Context
{
    protected bool $isDeployed = false;

    /**
     * @var Session[]
     */
    protected array $sessions = [];

    public function __construct(
        private readonly Project $project,
        private readonly Platform $platform,
        private readonly LoggerInterface $logger,
        private readonly SshFactory $sshFactory,
        private readonly Archiver $archiver,
        private readonly ?string $gitRef = null,
        private readonly bool $force = false,
        protected ?string $releaseId = null,
    ) {
        if (!$this->releaseId) {
            $this->generateReleaseId();
        }
    }

    public function connect(): void
    {
        foreach ($this->platform->getServers() as $server) {
            $ssh = $this->sshFactory->create($server);
            $session = new Session($server, $ssh, $this->getReleaseId());
            $session->login();
            $this->sessions[$server->getName()] = $session;
        }
    }

    /**
     * @param string[] $serversList
     */
    public function exec(string|callable $command, ?array $serversList = null, bool $addWorkingDir = true, bool $quiet = false): void
    {
        foreach ($this->sessions as $session) {
            if ($serversList && !in_array($session->getServer()->getName(), $serversList)) {
                continue;
            }

            if (is_string($command)) {
                if (!$quiet) {
                    $this->logger->command($command);
                }

                $result = $session->exec($command, $addWorkingDir);

                if ('' !== $result && !$quiet) {
                    $this->logger->result($result, $session->getServer());
                }
            } else {
                $command($session);
            }
        }
    }

    /**
     * @param string[] $serversList
     */
    public function execAsync(string $command, ?array $serversList = null, bool $addWorkingDir = true, bool $quiet = false): void
    {
        // if only one server: ignore asynchronous process
        if (($serversList && 1 === count($serversList)) || 1 === count($this->sessions)) {
            $this->exec($command, $serversList, $addWorkingDir, $quiet);

            return;
        }

        $process = [];
        if (!$quiet) {
            $this->logger->command($command);
        }

        foreach ($this->sessions as $session) {
            if ($serversList && !in_array($session->getServer()->getName(), $serversList)) {
                continue;
            }

            $child = $session->execAsync($command, $addWorkingDir);
            $child->start(function ($type, $output) use ($session, $quiet): void {
                if ('' === trim($output) && !$quiet) {
                    return;
                }

                $this->logger->result($output, $session->getServer());
            });
            $process[] = $child;
        }

        foreach ($process as $child) {
            $child->wait();
            if (!$child->isSuccessful()) {
                throw new \RuntimeException($child->getErrorOutput());
            }
        }
    }

    /**
     * @param ?string[] $exclude
     * @param ?string[] $serversList
     */
    public function copy(string $path, ?array $exclude, ?array $serversList = null): void
    {
        $this->logger->command(sprintf('COPY [local] %s to [remote] %s', $path, $path));

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('"%s" does not exist', $path));
        }

        $this->logger->info('    Copy preparation ...');
        $archive = $this->archiver->archive($path, $exclude);
        $archiveFileName = $this->archiver->getArchiveFileName($path);

        $this->logger->info('    Send data ...');
        $this->exec(static function (Session $session) use ($archive, $archiveFileName): void {
            $targetPath = Path::join($session->getReleasePath(), $archiveFileName);
            $session->copy($archive->getPath(), $targetPath);
        }, $serversList);

        $this->logger->info('    Untar data...');
        $this->execAsync(sprintf('tar xzvf %s', $archiveFileName), $serversList, quiet: true);
        $this->execAsync(sprintf('rm %s', $archiveFileName), $serversList, quiet: true);
        $this->archiver->clear($path);
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getGitRef(): ?string
    {
        return $this->gitRef;
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function isDeployed(): ?bool
    {
        return $this->isDeployed;
    }

    public function setDeployed(bool $isDeployed): static
    {
        $this->isDeployed = $isDeployed;

        return $this;
    }

    private function generateReleaseId(): void
    {
        $date = new \DateTime();

        $this->releaseId = sprintf(
            '%s.%s.%s-%s%s.%s',
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $date->format('H'),
            $date->format('i'),
            random_int(100, 999)
        );
    }

    public function getReleaseId(): string
    {
        return $this->releaseId;
    }
}
