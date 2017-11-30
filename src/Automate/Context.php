<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;


use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use phpDocumentor\Reflection\Types\Boolean;

class Context
{
    /**
     * @var string
     */
    private $releaseId;

    /**
     * @var string
     */
    private $gitRef;

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
     * @var Session[]
     */
    protected $sessions = array();

    /**
     * @var boolean
     */
    protected $force;

    /**
     * @var SessionFactory
     */
    protected $sessionFactory;

    /**
     * @param Project             $project
     * @param Platform            $platform
     * @param string              $gitRef
     * @param LoggerInterface     $logger
     * @param Boolean $force
     * @param SessionFactory|null $sessionFactory
     */
    public function __construct(Project $project, Platform $platform, $gitRef, LoggerInterface $logger, $force = false, SessionFactory $sessionFactory = null)
    {
        $this->project = $project;
        $this->platform = $platform;
        $this->gitRef = $gitRef;
        $this->logger = $logger;
        $this->force = $force;
        $this->sessionFactory = $sessionFactory ?: new SessionFactory();
    }

    /**
     * @return string
     */
    public function getGitRef()
    {
        return $this->gitRef;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return bool
     */
    public function isDeployed()
    {
        return $this->isDeployed;
    }

    /**
     * @param bool $isDeployed
     *
     * @return Context
     */
    public function setDeployed($isDeployed)
    {
        $this->isDeployed = $isDeployed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isForce()
    {
        return $this->force;
    }

    /**
     * @param bool $force
     *
     * @return Context
     */
    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    /**
     * connect servers.
     */
    public function connect()
    {
        $this->logger->section('Remote servers connection');

        foreach ($this->platform->getServers() as $server) {
            $session = $this->sessionFactory->create($server);
            $this->logger->response('Connection successful', $server->getName(), true);
            $this->sessions[$server->getName()] = $session;
        }
    }

    /**
     * @param Server $server
     *
     * @return Session
     */
    public function getSession(Server $server)
    {
        if (!isset($this->sessions[$server->getName()])) {
            throw new \RuntimeException('Unable to find session');
        }

        return $this->sessions[$server->getName()];
    }

    /**
     * Run command.
     *
     * @param string $command
     * @param bool   $verbose
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
     * Run on server.
     *
     * @param Server $server
     * @param string $command
     * @param bool   $addWorkingDir
     * @param bool   $verbose
     *
     * @return string
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
     * Get release path.
     *
     * @param Server $server
     *
     * @return string
     */
    public function getReleasePath(Server $server)
    {
        return $this->getReleasesPath($server).'/'.$this->getReleaseId();
    }

    /**
     * Get releases path.
     *
     * @param Server $server
     *
     * @return string
     */
    public function getReleasesPath(Server $server)
    {
        return $server->getPath().'/releases';
    }


    /**
     * Get shared path.
     *
     * @param Server $server
     *
     * @return string
     */
    public function getSharedPath(Server $server)
    {
        return $server->getPath().'/shared';
    }

    /**
     * Get current path.
     *
     * @param Server $server
     *
     * @return string
     */
    public function getCurrentPath(Server $server)
    {
        return $server->getPath().'/current';
    }

    /**
     * Get a release ID.
     *
     * @return string
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
}
