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

class Copy extends Action
{
    /**
     * @param null|string[] $only
     * @param null|string[] $exclude
     */
    public function __construct(
        private string $path,
        private ?array $exclude = null,
        protected ?array $only = null,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return ?string[]
     */
    public function getExclude(): ?array
    {
        return $this->exclude;
    }

    /**
     * @param ?string[] $exclude
     */
    public function setExclude(?array $exclude): self
    {
        $this->exclude = $exclude;

        return $this;
    }
}
