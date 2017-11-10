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

use Automate\Model\Server;

/**
 * Deployment workflow.
 */
class Deployer extends BaseWorkflow
{
    private $isDeployed = false;

    /**
     * Deploy project.
     *
     * @param string $gitRef
     *
     * @return bool
     */
    public function deploy($gitRef = null)
    {
        try {
            $this->connect();
            $this->initLockFile();
            $this->prepareRelease($gitRef);
            $this->runHooks($this->project->getPreDeploy(), 'Pre deploy');
            $this->initShared();
            $this->runHooks($this->project->getOnDeploy(), 'On deploy');
            $this->activateSymlink();
            $this->isDeployed = true;
            $this->runHooks($this->project->getPostDeploy(), 'Post deploy');
            $this->clearReleases();
            $this->clearLockFile();
            if (count($this->project->getGitlab())){
                $this->sendTriggerJobSuccess(true);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            if (count($this->project->getGitlab())){
                $this->sendTriggerJobSuccess(false);
            }
            try {
                if (!$this->isDeployed){
                    $this->moveToFailedReleases();
                }
                $this->clearLockFile();
           } catch (\Exception $e) {}
        }

        return false;
    }

    /**
     * Allow to send a trigger job to Gitlab
     * if the deployment is success or failed
     * only if you're deploying from your remote (not gitlab)
     */
    public function sendTriggerJobSuccess($success)
    {
        if (getenv('GITLAB_CI') === false ) {
            $gitlabUri = $this->project->getGitlab()["uri"];
            $gitlabVariables = $this->project->getGitlab()["variables"];
            $client = new \GuzzleHttp\Client();

            if ($success){
               $client->request(
                    'POST',
                    $gitlabUri . "/api/v4/projects/"
                    . $gitlabVariables["id_project"]
                    . '/trigger/pipeline?ref=' . $gitlabVariables["ref"]
                    . '&token=' . $gitlabVariables["token_trigger"]
                    . '&variables[ENVIRONMENT_NAME]=' . $gitlabVariables["environment"]
                    . '&variables[DEPLOY_SUCCESS_MSG]=' . $gitlabVariables["deploy_successed_msg"]
                    , ['verify' => false]
                );
            }else{
                $client->request(
                    'POST',
                    $gitlabUri . "/api/v4/projects/"
                    . $gitlabVariables["id_project"]
                    . '/trigger/pipeline?ref=' . $gitlabVariables["ref"]
                    . '&token=' . $gitlabVariables["token_trigger"]
                    . '&variables[ENVIRONMENT_NAME]=' . $gitlabVariables["environment"]
                    . '&variables[DEPLOY_FAILED_MSG]=' . $gitlabVariables["deploy_failed_msg"]
                    , ['verify' => false]
                );
            }
        }
    }

    /**
     * Check if a deployment is already in progress
     * and create lock file
     */
    public function initLockFile()
    {
        foreach($this->platform->getServers() as $server) {
            $session = $this->getSession($server);
            if($session->exists($this->getLockFilePath($server))) {
                throw new \RuntimeException('A deployment is already in progress');
            }
        }

        foreach($this->platform->getServers() as $server) {
            $session = $this->getSession($server);
            $session->touch($this->getLockFilePath($server));
        }
    }

    /**
     * remove lock file
     */
    public function clearLockFile()
    {
        foreach($this->platform->getServers() as $server) {
            $session = $this->getSession($server);
            $session->rm($this->getLockFilePath($server));
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
            $this->platform->getDefaultBranch()
        ), true);

        if ($gitRef) {
            $listTagsCommand = sprintf('git tag --list \'%s\'', $gitRef);
            $this->logger->command($listTagsCommand);
            foreach ($this->platform->getServers() as $server) {
                if ($gitRef && $this->doRun($server, $listTagsCommand, true)) {
                    // checkout a tag
                    $command = sprintf('git checkout tags/%s', $gitRef);
                } else {
                    // checkout branch or revision
                    $command = sprintf('git checkout %s', $gitRef);
                }

                $this->logger->command($command, true);
                $this->doRun($server, $command, true, true);
            }
        }
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
                if ('' !== $command->getCmd() && '#' !== substr(trim($command->getCmd()), 0, 1)) {
                    $this->run($command->getCmd(), true, $command->getOnly());
                }
            }
        }
    }

    /**
     * Setting up shared items.
     */
    private function initShared()
    {
        $folders = $this->project->getSharedFolders();
        $files = $this->project->getSharedFiles();

        if (count($folders) || count($files)) {
            $this->logger->section('Setting up shared items');
            foreach ($this->platform->getServers() as $server) {
                foreach ($folders as $folder) {
                    $this->doShared($folder, $server, true);
                }
                foreach ($files as $file) {
                    $this->doShared($file, $server, false);
                }
            }
        }
    }

    /**
     * @param $path
     * @param Server $server
     * @param bool   $isDirectory
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
        $this->logger->response(sprintf('%s --> %s', $releasePath, $sharedPath), $server->getName(), true);
        $session->symlink($sharedPath, $releasePath);
    }

    /**
     * deploy.
     */
    private function activateSymlink()
    {
        $this->logger->section('Publish new release');

        foreach ($this->platform->getServers() as $server) {
            $this->logger->response(sprintf('%s --> %s', $this->getCurrentPath($server), $this->getReleasePath($server)), $server->getName(), true);
            $this->getSession($server)->symlink($this->getReleasePath($server), $this->getCurrentPath($server));
        }
    }

    private function moveToFailedReleases(){
        $this->logger->section('Move this release to a failedRelease and clear olds failed releases');
        foreach ($this->platform->getServers() as $server) {
            $session = $this->getSession($server);

            $release = $this->getReleasePath($server);
            $this->logger->response('mv '.$release. ' '.$release."-failed", $server->getName(), true);

            $session->mv($release, $release."-failed");
        }

        $this->clearReleases(true);
    }

    private function clearReleases($failed = false)
    {
        if ($failed){
            $this->logger->section('Clear olds failed releases');
        }else{
            $this->logger->section('Clear olds releases');
        }

        foreach ($this->platform->getServers() as $server) {
            $session = $this->getSession($server);

            $releasesList = $session->listDirectory($this->getReleasesPath($server));
            $releasesList = array_map('trim', $releasesList);
            rsort($releasesList);

            if ($failed){
                $releases = array_filter($releasesList, function ($release) {
                    return preg_match('/[0-9]{4}\.[0-9]{2}\.[0-9]{2}-[0-9]{4}\.[0-9]{3}-failed/', $release);
                });
                $keep = 1;
            }else{
                $releases = array_filter($releasesList, function ($release) {
                    return preg_match('/[0-9]{4}\.[0-9]{2}\.[0-9]{2}-[0-9]{4}\.[0-9]{3}$/', $release);
                });
                $keep = $this->platform->getMaxReleases();
            }

            while ($keep > 0) {
                array_shift($releases);
                $keep--;
            }

            //Clear all Failed Releases if deployment is OK.
            if (!$failed){
                $releasesFailed = array_filter($releasesList, function ($release) {
                    return preg_match('/[0-9]{4}\.[0-9]{2}\.[0-9]{2}-[0-9]{4}\.[0-9]{3}-failed/', $release);
                });

                foreach ($releasesFailed as $releaseFailed){
                    array_push($releases, $releaseFailed);
                }
            }

            foreach ($releases as $release) {
                $this->logger->response('rm -R '.$release, $server->getName(), true);
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
}
