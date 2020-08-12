<?php


namespace Automate;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $pluginManager;

    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder("automate");

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('repository')
                    ->isRequired()
                ->end()
                ->arrayNode('shared_files')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('shared_folders')
                    ->scalarPrototype()->end()
                ->end()
                ->append($this->addCommandsNode('pre_deploy'))
                ->append($this->addCommandsNode('on_deploy'))
                ->append($this->addCommandsNode('post_deploy'))
                ->append($this->addPlatformsNode())
                ->append($this->addPluginsNode())
            ->end();

        return $treeBuilder;
    }

    private function addCommandsNode($name)
    {
        $treeBuilder = new TreeBuilder($name);

        $node = $treeBuilder->getRootNode()
            ->arrayPrototype()
                ->beforeNormalization()
                    ->ifString()
                    ->then(function ($v) { return ['cmd' => $v]; })
                ->end()
                ->children()
                    ->scalarNode('cmd')->isRequired()->cannotBeEmpty()->end()
                    ->arrayNode('only')
                        ->defaultValue([])
                        ->beforeNormalization()
                        ->ifString()
                            ->then(function ($v) { return [$v]; })
                        ->end()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    private function addPlatformsNode()
    {
        $treeBuilder = new TreeBuilder('platforms');

        $node = $treeBuilder->getRootNode()
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->normalizeKeys(false)
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('default_branch')->isRequired()->cannotBeEmpty()->end()
                    ->integerNode('max_releases')->end()
                    ->append($this->addServersNode())
                ->end()
            ->end();

        return $node;
    }

    private function addServersNode()
    {
        $treeBuilder = new TreeBuilder('servers');

        $node = $treeBuilder->getRootNode()
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
                    ->integerNode('port')->end()
                ->end()
            ->end();

        return $node;
    }

    private function addPluginsNode()
    {
        $treeBuilder = new TreeBuilder('plugins');

        $node = $treeBuilder->getRootNode()->children();

        foreach ($this->pluginManager->getPlugins() as $plugin) {
            $node->append($plugin->getConfigurationNode());
        }

        return $node->end();
    }

}
