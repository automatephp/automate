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
use Ssh\Session;

/**
 * Deplyement workflow
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
     * @param Project $project
     * @param Platform $platform
     * @param LoggerInterface $logger
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
     * Deploy project
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
     * Prepare release
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
            $gitRef ?:$this->platform->getDefaultBranch()
        ));
    }

    /**
     * Run hook commands.
     *
     * @param array $commands
     * @param string $name section name
     */
    private function runHooks(array $commands, $name)
    {

        if(count($commands)) {
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
     * Setting up shared items
     */
    private function initShared()
    {
        $this->logger->section('Setting up shared items');

        $dirs = $this->project->getSharedFolders();
        $files = $this->project->getSharedFiles();

        foreach ($this->platform->getServers() as $server) {

            foreach ($dirs as $dir) {
                $dir = trim($dir);
                $dir = ltrim($dir, '/');
                $releaseDir = $this->getReleasePath($server) . '/' . $dir;
                $sharedDir  = $this->getSharedPath($server) . '/' . $dir;

                // Remove from source.
                $this->doRun($server, sprintf('if [ -d %s ]; then rm -rf %s; fi', $releaseDir, $releaseDir), false);

                // Create shared dir if it does not exist.
                $this->doRun($server, sprintf('mkdir -p %s', $sharedDir), false);

                // Create path to shared dir in release dir if it does not exist.
                // (symlink will not create the path and will fail otherwise)
                $this->doRun($server, sprintf('mkdir -p `dirname %s`', $releaseDir), false);

                // Symlink shared dir to release dir
                $this->doRun($server, sprintf('ln -nfs %s %s', $sharedDir, $releaseDir), false);
            }
            foreach ($files as $file) {
                $file = trim($file);
                $file = ltrim($file, '/');
                $releaseFile = $this->getReleasePath($server) . '/' . $file;
                $sharedFile  = $this->getSharedPath($server) . '/' . $file;

                // Remove from source.
                $this->doRun($server, sprintf('if [ -f %s ]; then rm -rf %s; fi', $releaseFile, $releaseFile), false);

                // Ensure dir is available in release
                $this->doRun($server, sprintf('if [ ! -d `dirname %s` ]; then mkdir -p `dirname %s`;fi', $releaseFile, $releaseFile), false);

                // Create dir of shared file
                $this->doRun($server, sprintf('mkdir -p `dirname %s`', $sharedFile), false);

                // Touch shared
                $this->doRun($server, sprintf('touch %s', $sharedFile), false);

                // Symlink shared dir to release dir
                $this->doRun($server, sprintf('ln -nfs %s %s', $sharedFile, $releaseFile), false);
            }
        }
    }

    /**
     * deploy
     */
    private function activateSymlink()
    {
        $this->logger->section('Publish new release');

        foreach ($this->platform->getServers() as $server) {
            $command = sprintf('ln -sfn %s %s', $this->getReleasePath($server), $this->getCurrentPath($server));
            $this->doRun($server, $command, false);
        }
    }

    private function clearReleases()
    {
        $this->logger->section('Clear olds releases');

        foreach ($this->platform->getServers() as $server) {

            $response = $this->doRun($server, sprintf('find `dirname %s` -maxdepth 1 -mindepth 1 -type d', $this->getReleasePath($server)), false);

            $releases = explode("\n", trim($response));

            rsort($releases);

            $keep = 3;

            while ($keep > 0) {
                array_shift($releases);
                --$keep;
            }
            foreach ($releases as $release) {
                $this->doRun($server, sprintf('rm -rf %s', $release), false);
            }
        }
    }

    /**
     * Create release directory.
     */
    private function createReleaseDirectory()
    {
        foreach ($this->platform->getServers() as $server) {
            $command = sprintf('mkdir -p %s', $this->getReleasePath($server));
            $this->doRun($server, $command, false);
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
     * @param bool $addWorkingDir
     *
     * @return string
     */
    private function doRun(Server $server, $command, $addWorkingDir = true)
    {
        $session = $this->sessions[$server->getName()];
        $realCommand = $addWorkingDir ? sprintf('cd %s; %s', $this->getReleasePath($server), $command) : $command;
        $response = $session->getExec()->run($realCommand);

        if($response) {
            $this->logger->response($response, $server->getName());
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
    private function getReleasePath(Server $server)
    {
        return $server->getPath().'/releases/'.$this->releaseId;
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
     * Generate a release ID
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