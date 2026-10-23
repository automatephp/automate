<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Workflow;

use Automate\DispatcherFactory;
use Automate\Event\RollbackEvent;
use Automate\Event\RollbackEvents;
use Automate\Model\Action;
use Automate\Model\Command;
use Symfony\Component\Filesystem\Path;

/**
 * Rollback workflow.
 */
readonly class Rollbacker
{
    // same folder as a failed deployment: inspectable until the next deployment removes it
    private const string FAILED_FOLDER = 'failed';

    /**
     * @param ?string $release release to go back to, the one preceding "current" by default
     */
    public function __construct(private Context $context, private ?string $release = null)
    {
    }

    /**
     * Point "current" to an older release on every server, then move the release that was left to releases/failed.
     */
    public function rollback(): bool
    {
        $dispatcher = DispatcherFactory::create();

        try {
            $this->context->connect();

            $dispatcher->dispatch(new RollbackEvent($this->context), RollbackEvents::INIT);

            // nothing is touched until every server has a valid target
            $releases = $this->resolveReleases();
            $this->activateRelease($releases);

            $this->runHooks($this->context->getProject()->getPostRollback(), $releases);
            // after the hooks: a failing one leaves the release in place
            $this->moveLeftRelease($releases);

            $dispatcher->dispatch(new RollbackEvent($this->context), RollbackEvents::TERMINATE);

            return true;
        } catch (\Exception $exception) {
            $this->context->getLogger()->error($exception->getMessage());
            try {
                $dispatcher->dispatch(new RollbackEvent($this->context), RollbackEvents::FAILED);
            } catch (\Exception) {
                // ignore exception
            }
        }

        return false;
    }

    /**
     * @return array<string, array{from: string, to: string}> release ids indexed by server name
     */
    private function resolveReleases(): array
    {
        $releases = [];

        $this->context->exec(function (Session $session) use (&$releases): void {
            $name = (string) $session->getServer()->getName();
            $current = $session->getCurrentReleaseId();
            if (null === $current) {
                throw new \RuntimeException(sprintf('[%s] "current" does not point to a release', $name));
            }

            $available = array_map(basename(...), array_map('trim', $session->listDirectory($session->getReleasesPath())));
            $available = array_values(array_filter($available, Session::isReleaseId(...)));
            sort($available, SORT_STRING);

            // the id ends up in a "mv": only a release the server has listed is accepted,
            // which also rejects a dangling link (only the name of the target is known)
            if (!in_array($current, $available, true)) {
                throw new \RuntimeException(sprintf('[%s] "current" points to "%s", which is not one of the releases', $name, $current));
            }

            if (null !== $this->release) {
                // the id only reaches a shell command once the server has listed it
                if (!in_array($this->release, $available, true)) {
                    throw new \RuntimeException(sprintf('[%s] Unknown release "%s", available: %s', $name, $this->release, implode(', ', $available)));
                }

                if ($this->release === $current) {
                    throw new \RuntimeException(sprintf('[%s] The release "%s" is already the current one', $name, $this->release));
                }

                $target = $this->release;
            } else {
                $older = array_filter($available, static fn (string $release): bool => $release < $current);
                if ([] === $older) {
                    throw new \RuntimeException(sprintf('[%s] There is no release before "%s"', $name, $current));
                }

                $target = end($older);
            }

            $releases[$name] = ['from' => $current, 'to' => $target];
        });

        $targets = array_unique(array_column($releases, 'to'));
        if (count($targets) > 1) {
            throw new \RuntimeException(sprintf('The servers do not agree on the release to go back to (%s), choose one explicitly', implode(', ', $targets)));
        }

        return $releases;
    }

    /**
     * @param array<string, array{from: string, to: string}> $releases
     */
    private function activateRelease(array $releases): void
    {
        $this->context->getLogger()->section('Publish previous release');

        $this->context->exec(function (Session $session) use ($releases): void {
            $target = $session->withReleaseId($releases[(string) $session->getServer()->getName()]['to']);

            $session->symlink($target->getReleasePath(), $session->getCurrentPath());
            $this->context->getLogger()->info(sprintf('%s --> %s', $session->getCurrentPath(), $target->getReleasePath()), $session->getServer());
        });
    }

    /**
     * @param Action[]                                       $actions
     * @param array<string, array{from: string, to: string}> $releases
     */
    private function runHooks(array $actions, array $releases): void
    {
        if ([] === $actions) {
            return;
        }

        $logger = $this->context->getLogger();
        $logger->section('Post rollback');

        foreach ($actions as $action) {
            if (!$action instanceof Command) {
                continue;
            }

            $logger->command($action->getCmd());

            $this->context->exec(function (Session $session) use ($action, $logger, $releases): void {
                $session = $session->withReleaseId($releases[(string) $session->getServer()->getName()]['to']);
                $result = $session->exec($action->getCmd(), workingDir: $session->getCurrentPath());
                if ('' !== trim($result)) {
                    $logger->result($result, $session->getServer());
                }
            }, $action->getOnly());
        }
    }

    /**
     * @param array<string, array{from: string, to: string}> $releases
     */
    private function moveLeftRelease(array $releases): void
    {
        $this->context->getLogger()->section('Move the left release');

        $this->context->exec(function (Session $session) use ($releases): void {
            $left = $session->withReleaseId($releases[(string) $session->getServer()->getName()]['from'])->getReleasePath();
            $failed = Path::join($session->getReleasesPath(), self::FAILED_FOLDER);

            // "mv" would nest the release inside an existing folder
            if ($session->exists($failed)) {
                $session->rm($failed, true);
            }

            $this->context->getLogger()->info(sprintf('Move %s to %s', $left, $failed), $session->getServer());
            $session->mv($left, $failed);
        });
    }
}
