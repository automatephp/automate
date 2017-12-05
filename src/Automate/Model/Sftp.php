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
 * Platform configuration.
 */
class Sftp
{
    /**
     * @var array
     */
    private $excludeFolders = array();

    /**
     * @var array
     */
    private $localBuild = array();

    /**
     * @return array
     */
    public function getExcludeFolders()
    {
        return $this->excludeFolders;
    }

    /**
     * @param array $excludeFolders
     *
     * @return Sftp
     */
    public function setExcludeFolders($excludeFolders)
    {
        $this->excludeFolders = $excludeFolders;

        return $this;
    }

    /**
     * @return array
     */
    public function getLocalBuild()
    {
        return $this->localBuild;
    }

    /**
     * @param array $localBuild
     *
     * @return Sftp
     */
    public function setLocalBuild($localBuild)
    {
        $this->localBuild = $localBuild;

        return $this;
    }

}
