<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Romaric Paul <romaric.paul@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Model;

/**
 * Gitlab configuration.
 */
class Gitlab
{
    /**
     * @var string
     */
    private $uri;

    /**
     * @var int
     */
    private $idProject;

    /**
     * @var string
     */
    private $tokenTrigger;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $ref;

    /**
     * @var string
     */
    private $deploySuccessedMsg;

    /**
     * @var string
     */
    private $deployFailedMsg;

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     *
     * @return Gitlab
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdProject()
    {
        return $this->idProject;
    }

    /**
     * @param int $idProject
     *
     * @return Gitlab
     */
    public function setIdProject($idProject)
    {
        $this->idProject = $idProject;

        return $this;
    }

    /**
     * @return string
     */
    public function getTokenTrigger()
    {
        return $this->tokenTrigger;
    }

    /**
     * @param string $tokenTrigger
     *
     * @return Gitlab
     */
    public function setTokenTrigger($tokenTrigger)
    {
        $this->tokenTrigger = $tokenTrigger;

        return $this;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param string $tokenTrigger
     *
     * @return Gitlab
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     *
     * @return Gitlab
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeploySuccessedMsg()
    {
        return $this->deploySuccessedMsg;
    }

    /**
     * @param string $deploySuccessedMsg
     *
     * @return Gitlab
     */
    public function setDeploySuccessedMsg($deploySuccessedMsg)
    {
        $this->deploySuccessedMsg = $deploySuccessedMsg;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeployFailedMsg()
    {
        return $this->deployFailedMsg;
    }

    /**
     * @param string $deployFailedMsg
     *
     * @return Gitlab
     */
    public function setDeployFailedMsg($deployFailedMsg)
    {
        $this->deployFailedMsg = $deployFailedMsg;

        return $this;
    }
}
