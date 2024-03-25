<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Model;

class Platform
{
    /**
     * @param array<string, Server> $servers
     */
    public function __construct(
        private ?string $name = null,
        private ?string $defaultBranch = null,
        private ?int $maxReleases = null,
        private array $servers = [],
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDefaultBranch(): ?string
    {
        return $this->defaultBranch;
    }

    public function setDefaultBranch(string $defaultBranch): self
    {
        $this->defaultBranch = $defaultBranch;

        return $this;
    }

    public function getMaxReleases(): ?int
    {
        return $this->maxReleases;
    }

    public function setMaxReleases(int $maxReleases): self
    {
        $this->maxReleases = $maxReleases;

        return $this;
    }

    /**
     * @return $this
     */
    public function addServer(Server $server): self
    {
        $this->servers[] = $server;

        return $this;
    }

    /**
     * @return Server[]
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    public function getServer(string $name): Server
    {
        foreach ($this->servers as $server) {
            if ($name === $server->getName()) {
                return $server;
            }
        }

        throw new \InvalidArgumentException(sprintf('Missing server %s', $name));
    }
}
