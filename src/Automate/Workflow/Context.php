<?php

namespace Automate\Workflow;

use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;

class Context
{
    protected string $releaseId;

    protected bool $isDeployed = false;

    /**
     * @var Session[]
     */
    protected array $sessions = [];

    public function __construct(
        protected Project $project,
        protected Platform $platform,
        protected LoggerInterface $logger,
        protected ?string $gitRef = null,
        protected bool $force = false,
        protected ?SessionFactory $sessionFactory = null
    ) {
        if (!$this->sessionFactory instanceof SessionFactory) {
            $this->sessionFactory = new SessionFactory();
        }

        $this->generateReleaseId();
    }

    public function connect(): void
    {
        foreach ($this->platform->getServers() as $server) {
            $this->sessions[$server->getName()] = $this->sessionFactory->create($server, $this->getReleaseId());
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
