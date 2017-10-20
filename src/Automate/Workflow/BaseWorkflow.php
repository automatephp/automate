<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Workflow;

use Automate\Logger\LoggerInterface;
use Automate\Model\Command;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Session;
use Automate\SessionFactory;

class BaseWorkflow
{
    /**
     * @var string
     */
    private $releaseId;

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
     * @var Session[]
     */
    protected $sessions = array();

    /**
     * @var SessionFactory
     */
    protected $sessionFactory;

    /**
     * Workflow constructor.
     *
     * @param Project             $project
     * @param Platform            $platform
     * @param LoggerInterface     $logger
     * @param SessionFactory|null $sessionFactory
     */
    public function __construct(Project $project, Platform $platform, LoggerInterface $logger, SessionFactory $sessionFactory = null)
    {
        $this->project = $project;
        $this->platform = $platform;
        $this->logger = $logger;
        $this->sessionFactory = $sessionFactory ?: new SessionFactory();
    }

    /**
     * connect servers.
     */
    protected function connect()
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
    protected function getSession(Server $server)
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
    protected function run($command, $verbose = false, $specificServer = null)
    {
        $this->logger->command($command, $verbose);

        $servers = $this->platform->getServers();

        foreach ($servers as $server) {
            if($specificServer && $server->getName() != $specificServer) {
                continue;
            }

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
    protected function doRun(Server $server, $command, $addWorkingDir = true, $verbose = false)
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
    protected function getReleasePath(Server $server)
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
    protected function getReleasesPath(Server $server)
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
    protected function getSharedPath(Server $server)
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
    protected function getCurrentPath(Server $server)
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
                rand(1, 999)
            );
        }

        return $this->releaseId;
    }
}
