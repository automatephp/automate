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

interface ContextInterface
{
    public function connect(): void;

    public function getSession(Server $server): SessionInterface;

    public function getGitRef(): ?string;

    public function getProject(): Project;

    public function getPlatform(): Platform;

    public function getLogger(): LoggerInterface;

    public function isDeployed(): ?bool;

    public function setDeployed(bool $isDeployed): static;

    public function isForce(): bool;

    public function setForce(bool $force): static;

    public function getReleaseId(): string;

    /**
     * @param ?array<string> $specificServers
     */
    public function run(string $command, bool $verbose = false, ?array $specificServers = null, bool $addWorkingDir = true): void;

    public function doRun(Server $server, string $command, bool $addWorkingDir = true, bool $verbose = false): ?string;

    public function getReleasePath(Server $server): string;

    public function getReleasesPath(Server $server): string;

    public function getSharedPath(Server $server): string;

    public function getCurrentPath(Server $server): string;
}
