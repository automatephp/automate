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

class CacheToolPlugin implements PluginInterface
{

    const PHAR_URL = 'http://gordalina.github.io/cachetool/downloads/cachetool.phar';

    /**
     * @var array
     */
    protected $configuration;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            DeployEvents::TERMINATE => 'onTerminate',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function register(Project $project)
    {
        $this->configuration = $project->getPlugin($this->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cache_tool';
    }

    /**
     * @param DeployEvent $event
     */
    public function onTerminate(DeployEvent $event)
    {
        if($this->configuration) {

            $context = $event->getContext();
            $fastcgi = isset($this->configuration['fastcgi']) ? sprintf('--fcgi="%s"', $this->configuration['fastcgi']) : '--fcgi';

            $context->run('curl -sO ' . self::PHAR_URL);

            if(isset($this->configuration['opcache']) && $this->configuration['opcache']) {
                $context->run('php cachetool.phar opcache:reset ' . $fastcgi, true);
            }

            if(isset($this->configuration['apcu']) && $this->configuration['apcu']) {
                $context->run('php cachetool.phar apcu:cache:clear ' . $fastcgi, true);
            }

            if(isset($this->configuration['apc']) && $this->configuration['apc']) {
                $context->run('php cachetool.phar apc:cache:clear ' . $fastcgi, true);
            }

            $context->run('rm cachetool.phar');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationSchema()
    {
        return [
            '_type' => 'array',
            '_children' => [
                'fastcgi' => ['_type' => 'text'],
                'opcache' => ['_type' => 'boolean'],
                'apcu' => ['_type' => 'boolean'],
                'apc' => ['_type' => 'boolean'],
            ]
        ];
    }
}
