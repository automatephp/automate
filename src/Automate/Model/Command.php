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
 * Command configuration.
 */
class Command
{
    /**
     * @var string
     */
    private $cmd;

    /**
     * @var array
     */
    private $only;

    /**
     * @return string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * @param string $cmd
     *
     * @return Command
     */
    public function setCmd($cmd)
    {
        $this->cmd = $cmd;

        return $this;
    }

    /**
     * @return array
     */
    public function getOnly()
    {
        return $this->only;
    }

    /**
     * @param array $only
     *
     * @return Command
     */
    public function setOnly(array $only)
    {
        $this->only = $only;

        return $this;
    }
}
