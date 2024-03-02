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
    /**
     * @var string
     */
    private $repository;

    /**
     * @var array
     */
    private $sharedFiles = [];

    /**
     * @var array
     */
    private $sharedFolders = [];

    /**
     * @var array
     */
    private $preDeploy = [];

    /**
     * @var array
     */
    private $onDeploy = [];

    /**
     * @var array
     */
    private $postDeploy = [];

    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @var Platform[]
     */
    private $plaforms = [];

    /**
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param string $repository
     *
     * @return Project
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @return array
     */
    public function getSharedFiles()
    {
        return $this->sharedFiles;
    }

    /**
     * @return Project
     */
    public function setSharedFiles(array $sharedFiles)
    {
        $this->sharedFiles = $sharedFiles;

        return $this;
    }

    /**
     * @return array
     */
    public function getSharedFolders()
    {
        return $this->sharedFolders;
    }

    /**
     * @return Project
     */
    public function setSharedFolders(array $sharedFolders)
    {
        $this->sharedFolders = $sharedFolders;

        return $this;
    }

    /**
     * @return array
     */
    public function getPreDeploy()
    {
        return $this->preDeploy;
    }

    /**
     * @return Project
     */
    public function setPreDeploy(array $preDeploy)
    {
        $this->preDeploy = $preDeploy;

        return $this;
    }

    /**
     * @return array
     */
    public function getOnDeploy()
    {
        return $this->onDeploy;
    }

    /**
     * @return Project
     */
    public function setOnDeploy(array $onDeploy)
    {
        $this->onDeploy = $onDeploy;

        return $this;
    }

    /**
     * @return array
     */
    public function getPostDeploy()
    {
        return $this->postDeploy;
    }

    /**
     * @return Project
     */
    public function setPostDeploy(array $postDeploy)
    {
        $this->postDeploy = $postDeploy;

        return $this;
    }

    /**
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @param array
     * @param mixed $plugins
     *
     * @return Project
     */
    public function setPlugins($plugins)
    {
        $this->plugins = $plugins;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return null|array
     */
    public function getPlugin($name)
    {
        if (isset($this->plugins[$name])) {
            return $this->plugins[$name];
        }

        return null;
    }

    /**
     * @return Project
     */
    public function addPlatform(Platform $platform)
    {
        $this->plaforms[$platform->getName()] = $platform;

        return $this;
    }

    /**
     * @return Platform[]
     */
    public function getPlatforms()
    {
        return $this->plaforms;
    }

    /**
     * @param mixed $name
     *
     * @return Platform
     */
    public function getPlatform($name)
    {
        if (!isset($this->plaforms[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing platform %s', $name));
        }

        return $this->plaforms[$name];
    }
}
