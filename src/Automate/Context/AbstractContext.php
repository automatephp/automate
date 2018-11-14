<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Context;

use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;

abstract class AbstractContext implements ContextInterface
{
    /**
     * @var string
     */
    protected $releaseId;

    /**
     * @var string
     */
    protected $gitRef;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Platform
     */
    protected $platform;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var boolean
     */
    protected $isDeployed;

    /**
     * @var boolean
     */
    protected $force;

    /**
     * @param Project             $project
     * @param Platform            $platform
     * @param string              $gitRef
     * @param LoggerInterface     $logger
     * @param Boolean $force
     */
    public function __construct(Project $project, Platform $platform, $gitRef, LoggerInterface $logger, $force = false)
    {
        $this->project = $project;
        $this->platform = $platform;
        $this->gitRef = $gitRef;
        $this->logger = $logger;
        $this->force = $force;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function connect();

    /**
     * {@inheritdoc}
     */
    abstract public function getSession(Server $server);

    /**
     * {@inheritdoc}
     */
    public function getGitRef()
    {
        return $this->gitRef;
    }

    /**
     * {@inheritdoc}
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function isDeployed()
    {
        return $this->isDeployed;
    }

    /**
     * {@inheritdoc}
     */
    public function setDeployed($isDeployed)
    {
        $this->isDeployed = $isDeployed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isForce()
    {
        return $this->force;
    }

    /**
     * {@inheritdoc}
     */
    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseId()
    {
        if (!$this->releaseId) {
            $date = new \DateTime();

            $this->releaseId = sprintf(
                '%s.%s.%s-%s%s.%s',
                $date->format('Y'),
                $date->format('m'),
                $date->format('d'),
                $date->format('H'),
                $date->format('i'),
                rand(100, 999)
            );
        }

        return $this->releaseId;
    }

    /**
     * {@inheritdoc}
     */
    public function run($command, $verbose = false, $specificServer = null)
    {
        $servers = $this->platform->getServers();

        foreach ($servers as $server) {
            if($specificServer && $server->getName() != $specificServer) {
                continue;
            }
            $this->logger->command($command, $verbose);
            $this->doRun($server, $command, true, $verbose);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(Server $server, $command, $addWorkingDir = true, $verbose = false)
    {
        $realCommand = $addWorkingDir ? sprintf('cd %s; %s', $this->getReleasePath($server), $command) : $command;
        $response = $this->getSession($server)->run($realCommand);

        if ($response) {
            $this->logger->response($response, $server->getName(), $verbose);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getReleasePath(Server $server)
    {
        return $this->getReleasesPath($server).'/'.$this->getReleaseId();
    }

    /**
     * {@inheritdoc}
     */
    public function getReleasesPath(Server $server)
    {
        return $server->getPath().'/releases';
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedPath(Server $server)
    {
        $serverSharedPath = $server->getSharedPath();

        // if the shared path is not configured on the server configuration
        if (empty($serverSharedPath)) {
            $serverSharedPath = $server->getPath().'/shared';
        }

        return $serverSharedPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPath(Server $server)
    {
        return $server->getPath().'/current';
    }
}
