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
     * @param Action[]                $preDeploy
     * @param Action[]                $onDeploy
     * @param Action[]                $postDeploy
     * @param array<string, Platform> $platforms
     */
    public function __construct(
        private ?string $repository = null,
        private array $sharedFiles = [],
        private array $sharedFolders = [],
        private array $preDeploy = [],
        private array $onDeploy = [],
        private array $postDeploy = [],
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
     * @return Action[]
     */
    public function getPreDeploy(): array
    {
        return $this->preDeploy;
    }

    /**
     * @param Action[] $preDeploy
     */
    public function setPreDeploy(array $preDeploy): self
    {
        $this->preDeploy = $preDeploy;

        return $this;
    }

    /**
     * @return Action[]
     */
    public function getOnDeploy(): array
    {
        return $this->onDeploy;
    }

    /**
     * @param Action[] $onDeploy
     */
    public function setOnDeploy(array $onDeploy): self
    {
        $this->onDeploy = $onDeploy;

        return $this;
    }

    /**
     * @return Action[]
     */
    public function getPostDeploy(): array
    {
        return $this->postDeploy;
    }

    /**
     * @param Action[] $postDeploy
     */
    public function setPostDeploy(array $postDeploy): self
    {
        $this->postDeploy = $postDeploy;

        return $this;
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
