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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class GitterPlugin extends AbstractNotificationPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gitter';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationNode()
    {
        $treeBuilder = new TreeBuilder('gitter');

        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('token')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('room')->isRequired()->cannotBeEmpty()->end()
                ->append($this->getMessagesNode())
            ->end();
    }

    /**
     * @param string $message
     * @param mixed  $eventName
     */
    protected function sendMessage($message, $eventName)
    {
        $uri = sprintf('https://api.gitter.im/v1/rooms/%s/chatMessages', $this->configuration['room']);

        $this->client->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->configuration['token']),
            ],
            'json' => [
                'text' => $message,
            ],
            'verify' => false,
        ]);
    }
}
