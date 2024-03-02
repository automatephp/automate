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
 * Server configuration.
 */
class Server
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $user;

    /**
     * @var string
     */
    private $sshKey;

    /**
     * @var int
     */
    private $password;

    /**
     * @var int
     */
    private $path;

    /**
     * @var string
     */
    private $sharedPath;

    /**
     * @var int
     */
    private $port;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Server
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return Server
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int $user
     *
     * @return Server
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getSshKey()
    {
        return $this->sshKey;
    }

    /**
     * @param string $sshKey
     *
     * @return Server
     */
    public function setSshKey($sshKey)
    {
        $this->sshKey = $sshKey;

        return $this;
    }

    /**
     * @return int
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param int $password
     *
     * @return Server
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return int
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $path
     *
     * @return Server
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getSharedPath()
    {
        return $this->sharedPath;
    }

    /**
     * @param string $sharedPath
     *
     * @return Server
     */
    public function setSharedPath($sharedPath)
    {
        $this->sharedPath = $sharedPath;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return Server
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }
}
