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
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
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

        $configuration = new Configuration();

        if (!file_exists($path) || !$data = file_get_contents($path)) {
            throw new \InvalidArgumentException(sprintf('Missing configuration file "%s', $path));
        }

        $data = Yaml::parse($data);

        $processedConfiguration = $processor->processConfiguration($configuration, [$data]);

        foreach ($processedConfiguration['platforms'] as $platformName => $platform) {
            $processedConfiguration['platforms'][$platformName]['name'] = $platformName;
            foreach ($platform['servers'] as $serverName => $server) {
                $processedConfiguration['platforms'][$platformName]['servers'][$serverName]['name'] = $serverName;
            }
        }

        return $this->getSerializer()->denormalize($processedConfiguration, Project::class);
    }

    private function getSerializer(): Serializer
    {
        $objectNormalizer = new ObjectNormalizer(
            nameConverter: new CamelCaseToSnakeCaseNameConverter(),
            propertyTypeExtractor: new PhpDocExtractor()
        );

        return new Serializer(
            [new CommandDenormalizer(), $objectNormalizer, new ArrayDenormalizer()],
            [new YamlEncoder()]
        );
    }
}
