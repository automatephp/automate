<?php

namespace Automate\Ssh;

use Automate\Model\Platform;
use Automate\Model\Server;

class SshFactory
{
    /**
     * @param array<string, string> $variables
     */
    public function __construct(
        private readonly Platform $platform,
        private readonly array $variables,
        private readonly string $configFile,
    ) {
    }

    public function create(Server $server): Ssh
    {
        return new Ssh($this->platform, $server, $this->variables, $this->configFile);
    }
}
