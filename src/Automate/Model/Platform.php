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

/**
 * Platform configuration.
 */
class Platform
{
    private ?string $name = null;

    private ?string $defaultBranch = null;

    private ?int $maxReleases = null;

    /**
     * @var Server[]
     */
    private ?array $servers = null;

    /**
     * @return ?string
     */
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
    public function getServers(): ?array
    {
        return $this->servers;
    }
}
