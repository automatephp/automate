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

class Project
{
    /**
     * @param string[]                $sharedFiles
     * @param string[]                $sharedFolders
     * @param Command[]               $preDeploy
     * @param Command[]               $onDeploy
     * @param Command[]               $postDeploy
     * @param array<string, mixed>    $plugins
     * @param array<string, Platform> $platforms
     */
    public function __construct(
        private ?string $repository = null,
        private array $sharedFiles = [],
        private array $sharedFolders = [],
        private array $preDeploy = [],
        private array $onDeploy = [],
        private array $postDeploy = [],
        private array $plugins = [],
        private array $platforms = [],
    ) {
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    public function setRepository(string $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSharedFiles(): array
    {
        return $this->sharedFiles;
    }

    /**
     * @param string[] $sharedFiles
     */
    public function setSharedFiles(array $sharedFiles): self
    {
        $this->sharedFiles = $sharedFiles;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSharedFolders(): array
    {
        return $this->sharedFolders;
    }

    /**
     * @param string[] $sharedFolders
     */
    public function setSharedFolders(array $sharedFolders): self
    {
        $this->sharedFolders = $sharedFolders;

        return $this;
    }

    /**
     * @return Command[]
     */
    public function getPreDeploy(): array
    {
        return $this->preDeploy;
    }

    /**
     * @param Command[] $preDeploy
     */
    public function setPreDeploy(array $preDeploy): self
    {
        $this->preDeploy = $preDeploy;

        return $this;
    }

    /**
     * @return Command[]
     */
    public function getOnDeploy(): array
    {
        return $this->onDeploy;
    }

    /**
     * @param Command[] $onDeploy
     */
    public function setOnDeploy(array $onDeploy): self
    {
        $this->onDeploy = $onDeploy;

        return $this;
    }

    /**
     * @return Command[]
     */
    public function getPostDeploy(): array
    {
        return $this->postDeploy;
    }

    /**
     * @param Command[] $postDeploy
     */
    public function setPostDeploy(array $postDeploy): self
    {
        $this->postDeploy = $postDeploy;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @param array<string, mixed> $plugins
     */
    public function setPlugins(array $plugins): self
    {
        $this->plugins = $plugins;

        return $this;
    }

    /**
     * @return ?array<string, mixed>
     */
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

    public function getPlatform(string $name): Platform
    {
        if (!isset($this->platforms[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing platform %s', $name));
        }

        return $this->platforms[$name];
    }
}
