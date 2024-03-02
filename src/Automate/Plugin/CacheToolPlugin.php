<?php
/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Plugin;

use Automate\Event\DeployEvent;
use Automate\Event\DeployEvents;
use Automate\Model\Project;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class CacheToolPlugin implements PluginInterface
{
    public const string PHAR_URL = 'https://gordalina.github.io/cachetool/downloads/';

    protected ?array $configuration = null;

    
    public static function getSubscribedEvents(): array
    {
        return [
            DeployEvents::TERMINATE => 'onTerminate',
        ];
    }

    
    public function register(Project $project): void
    {
        $this->configuration = $project->getPlugin($this->getName());
    }

    
    public function getName(): string
    {
        return 'cache_tool';
    }

    public function onTerminate(DeployEvent $event): void
    {
        if ($this->configuration) {
            $context = $event->getContext();
            $fastcgi = isset($this->configuration['fastcgi']) ? sprintf('--fcgi="%s"', $this->configuration['fastcgi']) : '--fcgi';
            $scriptName = 'cachetool.phar';

            if (isset($this->configuration['version'])) {
                $scriptName = sprintf('cachetool-%s.phar', $this->configuration['version']);
            }

            $context->run('curl -sO '.self::PHAR_URL.$scriptName);

            if (isset($this->configuration['opcache']) && $this->configuration['opcache']) {
                $context->run('php '.$scriptName.' opcache:reset '.$fastcgi, true);
            }

            if (isset($this->configuration['apcu']) && $this->configuration['apcu']) {
                $context->run('php '.$scriptName.' apcu:cache:clear '.$fastcgi, true);
            }

            if (isset($this->configuration['apc']) && $this->configuration['apc']) {
                $context->run('php '.$scriptName.' apc:cache:clear '.$fastcgi, true);
            }

            $context->run('rm '.$scriptName);
        }
    }

    
    public function getConfigurationNode(): \Symfony\Component\Config\Definition\Builder\NodeDefinition
    {
        $treeBuilder = new TreeBuilder('cache_tool');

        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('version')->end()
                ->scalarNode('fastcgi')->end()
                ->booleanNode('opcache')->end()
                ->booleanNode('apcu')->end()
                ->booleanNode('apc')->end()
            ->end();
    }
}
