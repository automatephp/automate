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
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
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

    public function extractCommands($data, $hookName)
    {
        $commands = [];

        $data = $this->extractValue($data, $hookName, []);
        foreach ($data as $item) {
            $commands[] = $this->normalizer->denormalize($item, Command::class);
        }

        return $commands;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return Project::class === $type;
    }
}
