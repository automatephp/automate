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

/**
 * Deplyement workflow.
 */
class Workflow
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var Platform
     */
    private $platform;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session[]
     */
    private $sessions = array();

    /**
     * @var string
     */
    private $releaseId;

    /**
     * @var SessionFactory
     */
    private $sessionFactory;

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
     * Deploy project.
     *
     * @param string $gitRef
     */
    public function deploy($gitRef = null)
    {
        $this->releaseId = $this->generateReleaseId();

        try {
            $this->connect();
            $this->prepareRelease($gitRef);
            $this->runHooks($this->project->getPreDeploy(), 'Pre deploy');
            $this->initShared();
            $this->runHooks($this->project->getOnDeploy(), 'On deploy');
            $this->activateSymlink();
            $this->runHooks($this->project->getPostDeploy(), 'Post deploy');
            $this->clearReleases();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * connect servers.
     */
    private function connect()
    {
        $this->logger->section('Remote servers connection');

        foreach ($this->platform->getServers() as $server) {
            $session = $this->sessionFactory->create($server);
            $this->logger->response('Connection successful', $server->getName());
            $this->sessions[$server->getName()] = $session;
        }
    }

    /**
     * Prepare release.
     *
     * @param string $gitRef
     */
    private function prepareRelease($gitRef = null)
    {
        $this->logger->section('Prepare Release');

        $this->createReleaseDirectory();

        $this->run(sprintf(
            'git clone %s -q --recursive -b %s .',
            $this->project->getRepository(),
            $gitRef ?: $this->platform->getDefaultBranch()
        ));
    }

    /**
     * Run hook commands.
     *
     * @param array  $commands
     * @param string $name     section name
     */
    private function runHooks(array $commands, $name)
    {
        if (count($commands)) {
            $this->logger->section($name);
            foreach ($commands as $command) {
                $command = trim($command);
                if ('' !== $command && '#' !== substr(trim($command), 0, 1)) {
                    $this->run($command);
                }
            }
        }
    }

    /**
     * Setting up shared items.
     */
    private function initShared()
    {
        $this->logger->section('Setting up shared items');
        $folders = $this->project->getSharedFolders();
        $files = $this->project->getSharedFiles();

        foreach ($this->platform->getServers() as $server) {
            foreach ($folders as $folder) {
                $this->doShared($folder, $server, true);
            }
            foreach ($files as $file) {
                $this->doShared($file, $server, false);
            }
        }
    }

    /**
     * @param string $path
     * @param Server $server
     */
    private function doShared($path, Server $server, $isDirectory)
    {
        $session = $this->getSession($server);

        $path = trim($path);
        $path = ltrim($path, '/');
        $releasePath = $this->getReleasePath($server).'/'.$path;
        $sharedPath = $this->getSharedPath($server).'/'.$path;

        // For the first deployment : create shared form source
        if (!$session->exists($sharedPath) && $session->exists($releasePath)) {
            $session->mv($releasePath, dirname($sharedPath));
        }

        // Remove from source
        if ($session->exists($releasePath)) {
            $session->rm($releasePath, true);
        }

        // Create path to shared dir in release dir if it does not exist.
        // (symlink will not create the path and will fail otherwise)
        if (!$session->exists(dirname($releasePath))) {
            $session->mkdir(dirname($releasePath), true);
        }

        // ensure shared file or directory exists
        if (!$session->exists($sharedPath)) {
            if ($isDirectory) {
                $session->mkdir($sharedPath, true);
            } else {
                $session->touch($sharedPath);
            }
        }

        // create symlink
        $session->symlink($sharedPath, $releasePath);
    }

    /**
     * deploy.
     */
    private function activateSymlink()
    {
        $this->logger->section('Publish new release');

        foreach ($this->platform->getServers() as $server) {
            $this->getSession($server)->symlink($this->getReleasePath($server), $this->getCurrentPath($server));
        }
    }

    private function clearReleases()
    {
        $this->logger->section('Clear olds releases');

        foreach ($this->platform->getServers() as $server) {
            $session = $this->getSession($server);

            $releases = $session->listDirectory($this->getReleasesPath($server));
            rsort($releases);

            $keep = $this->platform->getMaxReleases();

            while ($keep > 0) {
                array_shift($releases);
                --$keep;
            }
            foreach ($releases as $release) {
                $session->rm($release, true);
            }
        }
    }

    /**
     * Create release directory.
     */
    private function createReleaseDirectory()
    {
        foreach ($this->platform->getServers() as $server) {
            $this->getSession($server)->mkdir($this->getReleasePath($server), true);
        }
    }

    /**
     * Run command.
     *
     * @param string $command
     */
    private function run($command)
    {
        $this->logger->command($command);
        foreach ($this->platform->getServers() as $server) {
            $this->doRun($server, $command, true);
        }
    }

    /**
     * Run on server.
     *
     * @param Server $server
     * @param string $command
     * @param bool   $addWorkingDir
     *
     * @return string
     */
    private function doRun(Server $server, $command, $addWorkingDir = true)
    {
        $realCommand = $addWorkingDir ? sprintf('cd %s; %s', $this->getReleasePath($server), $command) : $command;
        $response = $this->getSession($server)->run($realCommand);

        if ($response) {
            $this->logger->response($response, $server->getName());
        }

        return $response;
    }

    /**
     * @param Server $server
     *
     * @return Session
     */
    private function getSession(Server $server)
    {
        if (!isset($this->sessions[$server->getName()])) {
            throw new \RuntimeException('Unable to find session');
        }

        return $this->sessions[$server->getName()];
    }

    /**
     * Get release path.
     *
     * @param Server $server
     *
     * @return string
     */
    private function getReleasePath(Server $server)
    {
        return $this->getReleasesPath($server).'/'.$this->releaseId;
    }

    /**
     * Get releases path.
     *
     * @param Server $server
     *
     * @return string
     */
    private function getReleasesPath(Server $server)
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
    private function getSharedPath(Server $server)
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
    private function getCurrentPath(Server $server)
    {
        return $server->getPath().'/current';
    }

    /**
     * Generate a release ID.
     *
     * @return string
     */
    private function generateReleaseId()
    {
        $date = new \DateTime();

        return sprintf(
            '%s.%s.%s-%s%s.%s',
            $date->format('Y'),
            $date->format('m'),
            $date->format('d'),
            $date->format('H'),
            $date->format('i'),
            rand(1, 999)
        );
    }
}
