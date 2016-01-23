<?php
/**
 * This file is part of PhergieUrl.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phergie\Tests\Irc\Plugin\React\Url\Filter;

use Phake;
use Phergie\Irc\Event\EventInterface;
use Phergie\Irc\Plugin\React\Url\Filter\UrlEvent;
/**
 * Tests for the UrlEvent class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\Url\Filter
 */
class UrlEventTest extends \PHPUnit_Framework_TestCase
{
    public function provideGetUrlSection()
    {
        yield [
            'http://phergie.org/',
            [
                'scheme' => 'http',
                'host'   => 'phergie.org',
                'port'   => false,
            ]
        ];

        yield [
            'https://phergie.org:80/',
            [
                'scheme' => 'https',
                'host'   => 'phergie.org',
                'port'   => 80,
            ]
        ];
    }

    /**
     * @dataProvider provideGetUrlSection
     */
    public function testGetUrlSection($url, array $iterations)
    {
        $event = Phake::mock(EventInterface::class);
        $urlEvent = new UrlEvent($url, $event);
        foreach ($iterations as $key => $value) {
            $this->assertSame($value, $urlEvent->getUrlSection($key));
        }
    }
}
