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
 * Platform configuration
 */
class Platform
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $defaultBranch;

    /**
     * @var integer
     */
    private $maxReleases;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @var Server[]
     */
    private $servers;

    /**
     * @param string $name
     * @return Platform
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultBranch()
    {
        return $this->defaultBranch;
    }

    /**
     * @param string $defaultBranch
     * @return Platform
     */
    public function setDefaultBranch($defaultBranch)
    {
        $this->defaultBranch = $defaultBranch;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxReleases()
    {
        return $this->maxReleases;
    }

    /**
     * @param int $maxReleases
     * @return Platform
     */
    public function setMaxReleases($maxReleases)
    {
        $this->maxReleases = $maxReleases;

        return $this;
    }

    /**
     * @param Server $server
     * @return $this
     */
    public function addServer(Server $server)
    {
        $this->servers[] = $server;

        return $this;
    }

    /**
     * @return Server[]
     */
    public function getServers()
    {
        return $this->servers;
    }
}