<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Ssh;

use Automate\Model\Platform;
use Automate\Model\Server;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

class Ssh
{
    private ?SFTP $sftp = null;

    /**
     * @param array<string, string> $variables
     */
    public function __construct(
        private readonly Platform $platform,
        private readonly Server $server,
        private readonly array $variables,
        private readonly string $configFile,
    ) {
    }

    public function login(): void
    {
        $this->sftp = new SFTP($this->server->getHost(), $this->server->getPort());

        // Connection with ssh key and optional
        if (null !== $this->server->getSshKey()) {
            if (!file_exists($this->server->getSshKey())) {
                throw new \Exception(sprintf('[%s] File "'.$this->server->getSshKey().'" not found', $this->server->getName()));
            }

            $key = PublicKeyLoader::load(file_get_contents($this->server->getSshKey()), $this->server->getPassword());
            if (!$this->sftp->login($this->server->getUser(), $key)) {
                throw new \Exception(sprintf('[%s] SSH key or passphrase is invalid', $this->server->getName()));
            }
        } elseif (!$this->sftp->login($this->server->getUser(), $this->server->getPassword())) {
            throw new \Exception(sprintf('[%s] Invalid user or password', $this->server->getName()));
        }

        $this->sftp->setTimeout(0);
    }

    public function exec(string $command): string
    {
        if (!$this->sftp instanceof SFTP) {
            throw new \RuntimeException('The connection is not active');
        }

        $rs = (string) $this->sftp->exec($command);

        if (0 !== $this->sftp->getExitStatus()) {
            throw new \RuntimeException($rs);
        }

        return $rs;
    }

    public function upload(string $path, string $target): void
    {
        if (!$this->sftp instanceof SFTP) {
            throw new \RuntimeException('The connection is not active');
        }

        $rs = $this->sftp->put($target, $path, SFTP::SOURCE_LOCAL_FILE);

        if (!$rs) {
            throw new \RuntimeException('Sftp error');
        }
    }

    public function execAsync(string $command): Process
    {
        $bin = \Phar::running(false);

        if ('' === $bin) {
            $bin = Path::join(dirname(__FILE__, 4), 'bin', 'automate');
        }

        return new Process([
            $bin, 'exec', $this->platform->getName(), $this->server->getName(), $command, '-c', $this->configFile,
        ], env: $this->variables, timeout: 0);
    }
}
