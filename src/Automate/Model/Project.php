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
 * Project configuration.
 */
class Project
{
    private ?string $repository = null;

    private array $sharedFiles = [];

    private array $sharedFolders = [];

    private array $preDeploy = [];

    private array $onDeploy = [];

    private array $postDeploy = [];

    private array $plugins = [];

    /**
     * @var Platform[]
     */
    private array $platforms = [];

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function getSharedFiles(): array
    {
        return $this->sharedFiles;
    }

    public function setSharedFiles(array $sharedFiles): self
    {
        $this->sharedFiles = $sharedFiles;

        return $this;
    }

    public function getSharedFolders(): array
    {
        return $this->sharedFolders;
    }

    public function setSharedFolders(array $sharedFolders): self
    {
        $this->sharedFolders = $sharedFolders;

        return $this;
    }

    public function getPreDeploy(): array
    {
        return $this->preDeploy;
    }

    public function setPreDeploy(array $preDeploy): self
    {
        $this->preDeploy = $preDeploy;

        return $this;
    }

    public function getOnDeploy(): array
    {
        return $this->onDeploy;
    }

    public function setOnDeploy(array $onDeploy): self
    {
        $this->onDeploy = $onDeploy;

        return $this;
    }

    public function getPostDeploy(): array
    {
        return $this->postDeploy;
    }

    public function setPostDeploy(array $postDeploy): self
    {
        $this->postDeploy = $postDeploy;

        return $this;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function setPlugins(array $plugins): self
    {
        $this->plugins = $plugins;

        return $this;
    }

    public function getPlugin(string $name): ?array
    {
        return $this->plugins[$name] ?? null;
    }

    public function addPlatform(Platform $platform): self
    {
        $this->platforms[$platform->getName()] = $platform;

        return $this;
    }

    /**
     * @return Platform[]
     */
    public function getPlatforms(): array
    {
        return $this->platforms;
    }

    public function getPlatform($name): Platform
    {
        if (!isset($this->platforms[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing platform %s', $name));
        }

        return $this->platforms[$name];
    }
}
