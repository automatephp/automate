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
 * Slack configuration.
 */
class Slack
{
    /**
     * @var string
     */
    private $hookUri;
    
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
    public function getHookUri()
    {
        return $this->hookUri;
    }

    /**
     * @param string $hookUri
     *
     * @return Slack
     */
    public function setHookUri($hookUri)
    {
        $this->hookUri = $hookUri;

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
     * @return Slack
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
     * @return Slack
     */
    public function setDeployFailedMsg($deployFailedMsg)
    {
        $this->deployFailedMsg = $deployFailedMsg;

        return $this;
    }
}
