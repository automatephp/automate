<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Context;

use Automate\Logger\LoggerInterface;
use Automate\Model\Platform;
use Automate\Model\Project;
use Automate\Model\Server;
use Automate\Session\SessionInterface;
use Automate\SessionFactory;

class SSHContext extends AbstractContext
{
    /**
     * @var SessionInterface[]
     */
    protected array $sessions = [];

    protected SessionFactory $sessionFactory;

    public function __construct(Project $project, Platform $platform, LoggerInterface $logger, ?string $gitRef = null, bool $force = false)
    {
        parent::__construct($project, $platform, $logger, $gitRef, $force);
        $this->sessionFactory = new SessionFactory();
    }

    public function connect(): void
    {
        $this->logger->section('Remote servers connection');

        foreach ($this->platform->getServers() as $server) {
            $session = $this->sessionFactory->create($server);
            $this->logger->response('Connection successful', $server->getName(), true);
            $this->sessions[$server->getName()] = $session;
        }
    }

    public function getSession(Server $server): SessionInterface
    {
        if (!isset($this->sessions[$server->getName()])) {
            throw new \RuntimeException('Unable to find session');
        }

        return $this->sessions[$server->getName()];
    }

    public function setSessionFactory(SessionFactory $sessionFactory): void
    {
        $this->sessionFactory = $sessionFactory;
    }
}
