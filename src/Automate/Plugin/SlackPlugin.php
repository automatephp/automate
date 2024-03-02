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

/**
 * Allow to send a notification to your channel Slack
 * if the deployment is success or failed
 * only if you're deploying from your remote (not from gitlab)
 *
 * @author Julien Jacottet <jjacottet@gmail.com>
 * @author Romaric Paul <romaric.paul@gmail.com>
 *
 */


class SlackPlugin extends AbstractNotificationPlugin
{
    
    public function getName(): string
    {
        return 'slack';
    }

    
    public function getConfigurationNode(): \Symfony\Component\Config\Definition\Builder\NodeDefinition
    {
        $treeBuilder = new TreeBuilder("slack");

        return $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('hook_uri')->isRequired()->cannotBeEmpty()->end()
                ->append($this->getMessagesNode())
            ->end();

    }

    
    protected function sendMessage(string $message, string $eventName): void
    {
        $this->client->request(
            'POST', $this->configuration['hook_uri'],
            [
                'json' => [
                    'text' => $message,
                ],
            ]
        );
    }
}
