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
    private $sharedFiles = array();

    /**
     * @var array
     */
    private $sharedFolders = array();

    /**
     * @var array
     */
    private $preDeploy = array();

    /**
     * @var array
     */
    private $onDeploy = array();

    /**
     * @var array
     */
    private $postDeploy = array();

    /**
     * @var Platform[]
     */
    private $plaforms = array();

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
     * @param array $sharedFiles
     *
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
     * @param array $sharedFolders
     *
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
     * @param array $preDeploy
     *
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
     * @param array $onDeploy
     *
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
     * @param array $postDeploy
     *
     * @return Project
     */
    public function setPostDeploy(array $postDeploy)
    {
        $this->postDeploy = $postDeploy;

        return $this;
    }

    /**
     * @param Platform $platform
     *
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
