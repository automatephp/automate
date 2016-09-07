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

/**
 * Inspector workflow.
 */
class Inspector extends BaseWorkflow
{
    /**
     * inspect project.
     *
     * @return bool
     */
    public function inspect()
    {
        try {
            $this->connect();
            $this->gitConnect();

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }

    private function gitConnect()
    {
        $this->logger->section('Check Git connection from remotes');

        $domain = $this->getGitRepositoryDomain($this->project->getRepository());

        $this->run(sprintf(
            'if [ ! -n "$(grep "^%s " ~/.ssh/known_hosts)" ]; then ssh-keyscan %s >> ~/.ssh/known_hosts 2>/dev/null; fi',
            $domain,
            $domain
        ));

        foreach ($this->platform->getServers() as $server) {
            $this->doRun($server, sprintf('git ls-remote %s', $this->project->getRepository()), false);
            $this->logger->response(sprintf('Git Access (%s) [OK]', $this->project->getRepository()), $server->getName(), true);
        }
    }

    /**
     * @return string
     */
    private function getGitRepositoryDomain($url)
    {
        preg_match('/@(.*):/', $url, $match);

        if (isset($match[1])) {
            return $match[1];
        }

        throw new \LogicException('Invalid repository name');
    }
}
