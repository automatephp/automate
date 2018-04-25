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
 * Allow to send a notification to your channel Sentry
 * if the deployment is success only
 * only if you're deploying from your remote (not from gitlab)
 *
 * @author Julien Jacottet <jjacottet@gmail.com>
 * @author Romaric Paul <romaric.paul@gmail.com>
 *
 */

class SentryPlugin extends AbstractNotificationPlugin
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sentry';
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
                    '_not_empty' => true
                ],
                'messages' => $this->getMessagesSchema()
            ]
        ];
    }

    /**
     * @param string $message
     * @param string $eventName
     */
    protected function sendMessage($message, $eventName)
    {
        if ($eventName === AbstractNotificationPlugin::TERMINATE){
            $res = $this->client->request(
                'POST', $this->checkUri($this->configuration['hook_uri']),
                [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'version' => (new \DateTime('now'))->format('Y-m-d H:i:s') . ' ' . $message
                    ],
                    'http_errors' => false
                ]
            );
        }
    }

    /**
     * @param string $uri
     * @return string
     */
    protected function checkUri($uri)
    {
        if (substr($uri, -1) !== '/'){
            $uri = $uri . '/';
        }

        return $uri;
    }
}
