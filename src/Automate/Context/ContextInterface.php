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

interface ContextInterface
{
    /**
     * connect servers.
     */
    public function connect();

    /**
     * Get serveur's Session.
     */
    public function getSession(Server $server);

    /**
     * Get GitRef.
     */
    public function getGitRef(): ?string;

    /**
     * Get Project.
     */
    public function getProject(): Project;

    /**
     * Get Platform.
     */
    public function getPlatform(): Platform;

    /**
     * Get Logger.
     */
    public function getLogger(): LoggerInterface;

    /**
     * Is Deployed.
     */
    public function isDeployed(): ?bool;

    public function setDeployed(bool $isDeployed): static;

    /**
     * Is Force.
     */
    public function isForce(): bool;

    public function setForce(bool $force): static;

    /**
     * Get a release ID.
     */
    public function getReleaseId(): string;

    /**
     * Execute e command.
     */
    public function run(string $command, bool $verbose = false, ?array $specificServers = null, bool $addWorkingDir = true);

    /**
     * Run on server.
     */
    public function doRun(Server $server, string $command, bool $addWorkingDir = true, bool $verbose = false): ?string;

    /**
     * Get release path.
     */
    public function getReleasePath(Server $server): string;

    /**
     * Get releases path.
     */
    public function getReleasesPath(Server $server): string;

    /**
     * Get shared path.
     */
    public function getSharedPath(Server $server): string;

    /**
     * Get current path.
     */
    public function getCurrentPath(Server $server): string;
}
