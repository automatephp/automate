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
use Automate\Serializer\PlatformDenormalizer;
use Automate\Serializer\ProjectDenormalizer;
use Automate\Serializer\ServerDenormalizer;
use Automate\Serializer\CommandDenormalizer;
use Automate\Serializer\SftpDenormalizer;
use RomaricDrigon\MetaYaml\MetaYaml;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration loader.
 */
class Loader
{
    /**
     * Load project configuration.
     *
     * @param string|null $path
     *
     * @return Project|object
     */
    public function load($path)
    {
        $pluginManager = new PluginManager();

        $schemaDescription = $this->getSchema();

        foreach ($pluginManager->getPlugins() as $plugin) {
            $schemaDescription['root']['_children']['plugins']['_children'][$plugin->getName()] = $plugin->getConfigurationSchema();
        }

        $schema = new MetaYaml($schemaDescription, true);

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('Missing configuration file "%s', $path));
        }

        $data = Yaml::parse(file_get_contents($path));
        $schema->validate($data);

        $this->strategyValidation($data);

        $serializer = new Serializer([
            new SftpDenormalizer(),
            new ProjectDenormalizer(),
            new PlatformDenormalizer(),
            new ServerDenormalizer(),
            new CommandDenormalizer(),
        ]);

        return $serializer->denormalize($data, Project::class);
    }

    /**
     * Schema definition.
     *
     * @see https://github.com/romaricdrigon/MetaYaml
     *
     * @return array
     */
    private function getSchema()
    {
        return [
            'root' => [
                '_type' => 'array',
                '_children' => [
                    'strategy' => [
                        '_type' => 'text',
                        '_required'  => true,
                        '_not_empty' => true,
                    ],
                    'repository' => [
                        '_type' => 'text',
                        '_required' => false,
                        '_not_empty' => true,
                    ],
                    'sftp' => [
                        '_type' => 'array',
                        '_children' => [
                            'exclude_folders' => [
                                '_type' => 'prototype',
                                '_prototype' => ['_type' => 'text'],
                            ],
                            'local_build' => [
                                '_type' => 'prototype',
                                '_prototype' => ['_type' => 'text'],
                            ]
                        ]
                    ],
                    'shared_files' => [
                        '_type' => 'prototype',
                        '_prototype' => ['_type' => 'text'],
                    ],
                    'shared_folders' => [
                        '_type' => 'prototype',
                        '_prototype' => ['_type' => 'text'],
                    ],
                    'pre_deploy' => [
                        '_type'    => 'partial',
                        '_partial' => 'command',
                    ],
                    'on_deploy' => [
                        '_type'    => 'partial',
                        '_partial' => 'command',
                    ],
                    'post_deploy' => [
                        '_type'    => 'partial',
                        '_partial' => 'command',
                    ],
                    'plugins' => [
                        '_type' => 'array',
                        '_children' => []
                    ],
                    'platforms' => [
                        '_type' => 'prototype',
                        '_min_items' => 1,
                        '_required' => true,
                        '_prototype' => [
                            '_type' => 'array',
                            '_children' => [
                                'default_branch' => [
                                    '_type' => 'text',
                                    '_required' => true,
                                    '_not_empty' => true,
                                ],
                                'max_releases' => [
                                    '_type' => 'number',
                                ],
                                'servers' => [
                                    '_type' => 'prototype',
                                    '_min_items' => 1,
                                    '_required' => true,
                                    '_prototype' => [
                                        '_type' => 'array',
                                        '_children' => [
                                            'host' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'user' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'password' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'path' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'port' => [
                                                '_type' => 'number'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'partials' => [
                'command' => [
                    '_type' => 'prototype',
                    '_prototype' => [
                        '_type' => 'choice',
                        '_choices' => [
                            [
                                '_type' => 'array',
                                '_children' => [
                                    'cmd' => [
                                        '_type' => 'text',
                                    ],
                                    'only' => [
                                        '_type' => 'text',
                                    ],
                                ]
                            ],
                            [
                                '_type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Can validate (or not) the strategy deployment
     * @param $data
     */
    private function strategyValidation($data)
    {
        if ($data['strategy'] === 'sftp' && !isset($data['sftp']))
        {
            throw new \InvalidArgumentException(sprintf('If you use "stfp" as "strategy", ou have to declare your 
            sftp schema. Please take a look in the documentation : http://automatephp.github.io'));
        }

        if ($data['strategy'] === 'git' && !isset($data['repository']))
        {
            throw new \InvalidArgumentException(sprintf('If you use "git" as "strategy", ou have to declare your 
            repository adresse. Please take a look in the documentation : http://automatephp.github.io'));
        }

    }
}
