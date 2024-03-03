<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate;

use Automate\Model\Project;
use Automate\Serializer\CommandDenormalizer;
use Automate\Serializer\PlatformDenormalizer;
use Automate\Serializer\ProjectDenormalizer;
use Automate\Serializer\ServerDenormalizer;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration loader.
 */
class Loader
{
    /**
     * Load project configuration.
     */
    public function load(string $path): Project
    {
        $processor = new Processor();

        $pluginManager = new PluginManager();
        $configuration = new Configuration($pluginManager);

        if (!file_exists($path) || !$data = file_get_contents($path)) {
            throw new \InvalidArgumentException(sprintf('Missing configuration file "%s', $path));
        }

        $data = Yaml::parse($data);

        $processedConfiguration = $processor->processConfiguration($configuration, [$data]);

        $serializer = new Serializer([
            new ProjectDenormalizer(),
            new PlatformDenormalizer(),
            new ServerDenormalizer(),
            new CommandDenormalizer(),
        ]);

        return $serializer->denormalize($processedConfiguration, Project::class);
    }
}
