<?php

namespace Automate\Workflow;

use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Ssh\SshFactory;

class Context
{
    protected string $releaseId;

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
        private readonly ?string $gitRef = null,
        private readonly bool $force = false,
    ) {
        $this->generateReleaseId();
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
    public function exec(string|callable $command, ?array $serversList = null, bool $addWorkingDir = true): void
    {
        foreach ($this->sessions as $session) {
            if ($serversList && !in_array($session->getServer()->getName(), $serversList)) {
                continue;
            }

            if (is_string($command)) {
                $this->logger->command($command);
                $result = $session->exec($command, $addWorkingDir);

                if ('' !== $result) {
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
    public function execAsync(string $command, ?array $serversList = null, bool $addWorkingDir = true): void
    {
        $process = [];
        $this->logger->command($command);

        foreach ($this->sessions as $session) {
            if ($serversList && !in_array($session->getServer()->getName(), $serversList)) {
                continue;
            }

            $child = $session->execAsync($command, $addWorkingDir);
            $child->start(function ($type, $output) use ($session): void {
                if ('' === trim($output)) {
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
