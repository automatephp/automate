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
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'slack';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationSchema()
    {
        return [
            '_type' => 'array',
            '_children' => [
                'hook_uri' => [
                    '_type' => 'text',
                    '_required' => true,
                    '_not_empty' => true,
                ],
                'messages' => $this->getMessagesSchema()
            ]
        ];
    }

    /**
     * @param string $message
     */
    protected function sendMessage($message, $eventName)
    {
        $client = new \GuzzleHttp\Client();

        $client->request(
            'POST', $this->configuration['hook_uri'],
            [
                'json' => [
                    'text' => $message
                ],
                'verify' => false
            ]
        );
    }
}
