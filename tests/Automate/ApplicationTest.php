<?php

/*
 * This file is part of the Automate package.
 *
 * (c) Julien Jacottet <jjacottet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Automate\Tests;

use Automate\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class ApplicationTest extends TestCase
{
    public function testApp(): void
    {
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(['--version']);
        $stream = fopen('php://memory', 'w', false);
        $output = new StreamOutput($stream);
        $app->run($input, $output);
        rewind($stream);
        $string = trim(fgets($stream));
        $string = preg_replace(
            ['/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', '/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', '/[\x03|\x1a]/'],
            ['', '', ''],
            $string
        );
        $this->assertEquals('Automate @dev', $string);

        $app->setVersion('1.2.3');
        rewind($stream);
        $app->run($input, $output);
        rewind($stream);
        $string = trim(fgets($stream));
        $string = preg_replace(
            ['/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', '/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', '/[\x03|\x1a]/'],
            ['', '', ''],
            $string
        );
        $this->assertEquals(
            'Automate version 1.2.3 build @git-commit@',
            $string
        );
    }
}
