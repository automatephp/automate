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

use Automate\Model\Command;
use Automate\Model\Project;
use Automate\Serializer\PlatformDenormalizer;
use Automate\Serializer\ProjectDenormalizer;
use Automate\Serializer\ServerDenormalizer;
use Automate\Serializer\CommandDenormalizer;
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
     * @return Project
     */
    public function load($path)
    {
        $schema = new MetaYaml($this->getSchema(), true);

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('Missing configuration file "%s', $path));
        }

        $data = Yaml::parse(file_get_contents($path));
        $schema->validate($data);

        $serializer = new Serializer([
            new ProjectDenormalizer(),
            new PlatformDenormalizer(),
            new ServerDenormalizer(),
            new CommandDenormalizer(),
        ]);

        return $serializer->denormalize($data, Project::class);
    }

    /**
     * Schema defintition.
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
                    'repository' => [
                        '_type' => 'text',
                        '_required' => true,
                        '_not_empty' => true,
                    ],
                    'gitlab' => [
                        '_type' => 'prototype',
                        '_required' => false,
                        '_prototype' => [
                            '_type' => 'array',
                            '_children' => [
                                'uri' => [
                                    '_type' => 'text',
                                    '_required' => true,
                                    '_not_empty' => true,
                                ],
                                'variables' => [
                                    '_type' => 'prototype',
                                    '_required' => true,
                                    '_prototype' => [
                                        '_type' => 'array',
                                        '_children' => [
                                            'id_project' => [
                                                '_type' => 'int',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'token_trigger' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'environment' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'ref' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'deploy_successed_msg' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                            'deploy_failed_msg' => [
                                                '_type' => 'text',
                                                '_required' => true,
                                                '_not_empty' => true,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'shared_files' => [
                        '_type' => 'prototype',
                        '_prototype' => ['_type' => 'text'],
                    ],
                    'shared_folders' => [
                        '_type'    => 'partial',
                        '_partial' => 'command',
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
}
