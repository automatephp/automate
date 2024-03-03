<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Serializer;

use Automate\Model\Command;
use Automate\Model\Platform;
use Automate\Model\Project;

/**
 * Project Denormalizer.
 *
 * @see http://symfony.com/doc/current/components/serializer.html
 */
class ProjectDenormalizer extends AbstractDenormalizer
{
    /**
     * @param array<string,mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $project = new Project();

        $project
            ->setRepository($this->extractValue($data, 'repository'))
            ->setSharedFiles($this->extractValue($data, 'shared_files', []))
            ->setSharedFolders($this->extractValue($data, 'shared_folders', []))
            ->setPreDeploy($this->extractCommands($data, 'pre_deploy'))
            ->setOnDeploy($this->extractCommands($data, 'on_deploy'))
            ->setPostDeploy($this->extractCommands($data, 'post_deploy'))
            ->setPlugins($this->extractValue($data, 'plugins', []));

        $platforms = $this->extractValue($data, 'platforms', []);

        foreach ($platforms as $name => $platformData) {
            $platformData['name'] = $name;
            $platform = $this->normalizer->denormalize($platformData, Platform::class);

            $project->addPlatform($platform);
        }

        return $project;
    }

    /**
     * @param string[] $data
     *
     * @return Command[]
     */
    public function extractCommands(array $data, string $hookName): array
    {
        $commands = [];

        $data = $this->extractValue($data, $hookName, []);
        foreach ($data as $item) {
            $commands[] = $this->normalizer->denormalize($item, Command::class);
        }

        return $commands;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Project::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Project::class => true,
        ];
    }
}
