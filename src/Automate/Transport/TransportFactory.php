<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Transport;

use Automate\Model\Platform;
use Automate\Model\Server;

class TransportFactory
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

    public function create(Server $server): TransportInterface
    {
        return new SshTransport($this->platform, $server, $this->variables, $this->configFile);
    }
}
