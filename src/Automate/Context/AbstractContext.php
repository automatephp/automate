<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Context;

use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Session\SessionInterface;

abstract class AbstractContext implements ContextInterface
{
    protected ?string $releaseId = null;

    protected ?bool $isDeployed = null;

    public function __construct(
        protected Project $project,
        protected Platform $platform,
        protected LoggerInterface $logger,
        protected ?string $gitRef = null,
        protected bool $force = false,
    ) {
    }

    abstract public function connect(): void;

    abstract public function getSession(Server $server): SessionInterface;

    public function getGitRef(): ?string
    {
        return $this->gitRef;
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

    public function isDeployed(): ?bool
    {
        return $this->isDeployed;
    }

    public function setDeployed(bool $isDeployed): static
    {
        $this->isDeployed = $isDeployed;

        return $this;
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function setForce(bool $force): static
    {
        $this->force = $force;

        return $this;
    }

    public function getReleaseId(): string
    {
        if (null === $this->releaseId) {
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

        return $this->releaseId;
    }

    public function run(string $command, bool $verbose = false, ?array $specificServers = null, bool $addWorkingDir = true): void
    {
        $servers = $this->platform->getServers();

        foreach ($servers as $server) {
            if ($specificServers && !in_array($server->getName(), $specificServers)) {
                continue;
            }

            $this->logger->command($command, $verbose);
            $this->doRun($server, $command, $addWorkingDir, $verbose);
        }
    }

    public function doRun(Server $server, string $command, bool $addWorkingDir = true, bool $verbose = false): ?string
    {
        $realCommand = $addWorkingDir ? sprintf('cd %s; %s', $this->getReleasePath($server), $command) : $command;

        $response = $this->getSession($server)->run($realCommand);

        if ('' !== $response) {
            $this->logger->response($response, $server->getName(), $verbose);
        }

        return $response;
    }

    public function getReleasePath(Server $server): string
    {
        return $this->getReleasesPath($server).'/'.$this->getReleaseId();
    }

    public function getReleasesPath(Server $server): string
    {
        return $server->getPath().'/releases';
    }

    public function getSharedPath(Server $server): string
    {
        $serverSharedPath = $server->getSharedPath();

        // if the shared path is not configured on the server configuration
        if (null === $serverSharedPath) {
            $serverSharedPath = $server->getPath().'/shared';
        }

        return $serverSharedPath;
    }

    public function getCurrentPath(Server $server): string
    {
        return $server->getPath().'/current';
    }
}
