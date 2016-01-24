<?php

namespace Phergie\Irc\Plugin\React\Url\Filter;

use Phake;
use Phergie\Irc\Event\EventInterface;

class UrlSectionFilterTest extends \PHPUnit_Framework_TestCase
{
    public function provideFilter()
    {
        yield [
            'host',
            'phergie.org',
            'http://phergie.org/',
            true,
        ];

        yield [
            'host',
            'phergie.org',
            'http://www.phergie.org/',
            false,
        ];

        yield [
            'host',
            '*.org',
            'http://phergie.org/',
            true,
        ];

        yield [
            'port',
            80,
            'http://phergie.org/',
            null,
        ];
    }

    /**
     * @dataProvider provideFilter
     */
    public function testFilter($section, $value, $url, $output)
    {
        $event = Phake::mock(EventInterface::class);
        $urlEvent = new UrlEvent($url, $event);
        $filter = new UrlSectionFilter($section, $value);
        $this->assertSame($output, $filter->filter($urlEvent));
    }

    public function provideStrictFilter()
    {
        yield [
            'host',
            'phergie.org',
            'http://phergie.org/',
            true,
        ];

        yield [
            'host',
            'phergie.org',
            'http://www.phergie.org/',
            false,
        ];

        yield [
            'host',
            '*.org',
            'http://phergie.org/',
            true,
        ];

        yield [
            'port',
            80,
            'http://phergie.org/',
            false,
        ];
    }

    /**
     * @dataProvider provideStrictFilter
     */
    public function testStrictFilter($section, $value, $url, $output)
    {
        $event = Phake::mock(EventInterface::class);
        $urlEvent = new UrlEvent($url, $event);
        $filter = new UrlSectionFilter($section, $value, true);
        $this->assertSame($output, $filter->filter($urlEvent));
    }
}
