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


class GitterPlugin extends AbstractChatPlugin
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
    public function getConfigurationSchema()
    {
        return [
            '_type' => 'array',
            '_children' => [
                'token' => [
                    '_type' => 'text',
                    '_required' => true,
                    '_not_empty' => true,
                ],
                'room' => [
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
    protected function sendMessage($message)
    {
        $client = new \GuzzleHttp\Client();

        $uri = sprintf('https://api.gitter.im/v1/rooms/%s/chatMessages', $this->configuration['room']);

        $client->request('POST', $uri, [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->configuration['token'])
            ],
            'json' => [
                'text' => $message
            ]
        ]);
    }
}