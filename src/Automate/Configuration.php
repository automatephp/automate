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

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

readonly class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('automate');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('repository')->end()
                ->arrayNode('shared_files')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('shared_folders')
                    ->scalarPrototype()->end()
                ->end()
                ->append($this->addCommandsNode('pre_deploy'))
                ->append($this->addCommandsNode('on_first_deploy'))
                ->append($this->addCommandsNode('on_deploy'))
                ->append($this->addCommandsNode('post_deploy'))
                ->append($this->addCommandsNode('on_remove', allowUpload: false))
                ->append($this->addPlatformsNode())
            ->end();

        return $treeBuilder;
    }

    private function addCommandsNode(string $name, bool $allowUpload = true): NodeDefinition
    {
        $treeBuilder = new TreeBuilder($name);

        $prototype = $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->beforeNormalization()
                    ->ifString()
                    ->then(static fn($v): array => ['cmd' => $v])
                ->end()
                ->children()
                    ->scalarNode('cmd')->end()
                    ->scalarNode('upload')->end()
                    ->arrayNode('exclude')->scalarPrototype()->end()->end()
                    ->arrayNode('only')
                        ->defaultValue([])
                        ->beforeNormalization()
                        ->ifString()
                            ->then(static fn($v): array => [$v])
                        ->end()
                        ->scalarPrototype()->end()
                    ->end()
                ->end();

        if (!$allowUpload) {
            $prototype
                ->validate()
                    ->ifTrue(static fn(array $action): bool => isset($action['upload']))
                    ->thenInvalid(sprintf('The "upload" action is not supported in "%s", only commands are.', $name))
                ->end();
        }

        return $prototype->end();
    }

    private function addPlatformsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('platforms');

        return $treeBuilder->getRootNode()
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->normalizeKeys(false)
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('default_branch')->isRequired()->cannotBeEmpty()->end()
                    ->integerNode('max_releases')->defaultValue(5)->end()
                    ->append($this->addServersNode())
                ->end()
            ->end();
    }

    private function addServersNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('servers');

        return $treeBuilder->getRootNode()
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->normalizeKeys(false)
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('password')->end()
                    ->scalarNode('ssh_key')->cannotBeEmpty()->end()
                    ->scalarNode('path')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('shared_path')->cannotBeEmpty()->end()
                    ->integerNode('port')->defaultValue(22)->end()
                ->end()
            ->end();
    }
}
