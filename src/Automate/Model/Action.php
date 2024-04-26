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

abstract class Action
{
    /**
     * @var ?string[]
     */
    protected ?array $only = null;

    /**
     * @return null|string[]
     */
    public function getOnly(): ?array
    {
        return $this->only;
    }

    /**
     * @param string[] $only
     */
    public function setOnly(array $only): self
    {
        $this->only = $only;

        return $this;
    }
}
