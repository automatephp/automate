<?php

namespace Automate\Ssh;

use Automate\Model\Platform;
use Automate\Model\Server;

readonly class SshFactory
{
    /**
     * @param array<string, string> $variables
     */
    public function __construct(
        private Platform $platform,
        private array $variables,
        private string $configFile,
    ) {
    }

    public function create(Server $server): Ssh
    {
        return new Ssh($this->platform, $server, $this->variables, $this->configFile);
    }
}
