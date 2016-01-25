<?php
/**
 * This file is part of PhergieUrl.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phergie\Tests\Irc\Plugin\React\Url;

use GuzzleHttp\Message\Response;
use Phake;
use Phergie\Irc\Bot\React\EventQueue;
use Phergie\Irc\Event\EventInterface;
use Phergie\Irc\Event\UserEvent;
use Phergie\Irc\Plugin\React\EventFilter\FilterInterface;
use Phergie\Irc\Plugin\React\EventFilter\NotFilter;
use Phergie\Irc\Plugin\React\Url\Plugin;
use React\Promise\FulfilledPromise;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\Url
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    protected static function getMethod($name) {
        $class = new \ReflectionClass('Phergie\Irc\Plugin\React\Url\Plugin');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin;
        $subscribedEvents = $plugin->getSubscribedEvents();
        $this->assertInternalType('array', $subscribedEvents);
        $this->assertSame(array(
            'irc.received.privmsg' => 'handleIrcReceived',
        ), $subscribedEvents);
    }

    public function testLogDebug() {
        $logger = $this->getMock('Monolog\Logger', array(
            'debug',
        ), array(
            'test',
        ));
        $logger->expects($this->once())
            ->method('debug')
            ->with('[Url]foo:bar');

        $plugin = new Plugin();
        $plugin->setLogger($logger);
        $plugin->logDebug('foo:bar');
    }

    public function testGetHandler() {
        $plugin = new Plugin();
        $this->assertInstanceOf('Phergie\Irc\Plugin\React\Url\UrlHandlerInterface', $plugin->getHandler());
        $this->assertInstanceOf('Phergie\Irc\Plugin\React\Url\DefaultUrlHandler', $plugin->getHandler());
    }

    public function testCustomHandler() {
        $handler = Phake::mock('Phergie\Irc\Plugin\React\Url\DefaultUrlHandler');
        $plugin = new Plugin(array(
            'handler' => $handler,
        ));
        $this->assertTrue(in_array('Phergie\Irc\Plugin\React\Url\UrlHandlerInterface', class_implements($plugin->getHandler())));
        $this->assertSame($handler, $plugin->getHandler());
    }

    public function testStdClassHandler() {
        $handler = new \stdClass();
        $plugin = new Plugin(array(
            'handler' => $handler,
        ));
        $this->assertInstanceOf('Phergie\Irc\Plugin\React\Url\DefaultUrlHandler', $plugin->getHandler());
    }

    public function testHandleIrcReceived() {
        $queue = Phake::mock('Phergie\Irc\Bot\React\EventQueue');

        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getParams()->thenReturn(array(
            'text' => 'test www.google.com test',
        ));

        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        Phake::when($plugin)->handleIrcReceived($event, $queue)->thenCallParent();

        $plugin->handleIrcReceived($event, $queue);

        Phake::verify($plugin)->handleUrl('www.google.com', $event, $queue);
    }

    public function testPreparePromises() {
        $plugin = new Plugin();
        $plugin->setLoop(Phake::mock('React\EventLoop\LoopInterface'));

        list($privateDeferred, $userFacingPromise) = self::getMethod('preparePromises')->invoke($plugin);

        $this->assertInstanceOf('React\Promise\Deferred', $privateDeferred);
        $this->assertInstanceOf('React\Promise\PromiseInterface', $userFacingPromise);
    }

    public function testSendMessage() {
        $target = '#foobar';
        $message = 'foo:bar';

        $url = Phake::mock('Phergie\Irc\Plugin\React\Url\Url');
        $handler = Phake::mock('Phergie\Irc\Plugin\React\Url\UrlHandlerInterface');
        Phake::when($handler)->handle($url)->thenReturn($message);

        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        Phake::when($plugin)->getHandler()->thenReturn($handler);

        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        Phake::when($event)->getSource()->thenReturn($target);

        $queue = Phake::mock('Phergie\Irc\Bot\React\EventQueue');

        self::getMethod('sendMessage')->invokeArgs($plugin, array($url, $event, $queue));

        Phake::verify($queue)->ircPrivmsg($target, $message);
    }

    public function testEmitUrlEvents() {
        $host = 'google.com';
        $eventName = 'url.host.' . $host;
        $url = 'http://' . $host . '/';

        $queue = Phake::mock('Phergie\Irc\Bot\React\EventQueue');
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');

        $emitter = Phake::mock('Evenement\EventEmitterInterface');
        Phake::when($emitter)->listeners($eventName)->thenReturn(array('foo' => 'bar'));

        $plugin = new Plugin();
        $plugin->setEventEmitter($emitter);
        $plugin->setLogger(Phake::mock('Monolog\Logger'));

        $this->assertNotTrue(self::getMethod('emitUrlEvents')->invokeArgs($plugin, array(
            'foo:bar',
            $url,
            $event,
            $queue,
        )));

        Phake::inOrder(
            Phake::verify($emitter)->listeners($eventName),
            Phake::verify($emitter)->emit($eventName, array($url, $event, $queue))
        );
    }

    public function testEmitShorteningEventsProvider() {
        return array(
            array(
                'url.shorten.google.com',
                'http://google.com/',
            ),
            array(
                'url.shorten.all',
                'http://google.com/',
            ),
        );
    }

    /**
     * @dataProvider testEmitShorteningEventsProvider
     */
    public function testEmitShorteningEvents($eventName, $url) {
        $logger = Phake::mock('Monolog\Logger');
        $privateDeferred = Phake::mock('React\Promise\Deferred');

        $emitter = Phake::mock('Evenement\EventEmitterInterface');
        Phake::when($emitter)->listeners($eventName)->thenReturn(array('foo' => 'bar'));

        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        Phake::when($plugin)->preparePromises()->thenReturn(array(
            $privateDeferred,
            Phake::mock('React\Promise\PromiseInterface'),
        ));
        Phake::when($plugin)->setEventEmitter($emitter)->thenCallParent();
        Phake::when($plugin)->setLogger($logger)->thenCallParent();

        $plugin->setEventEmitter($emitter);
        $plugin->setLogger($logger);

        $this->assertInstanceOf('React\Promise\PromiseInterface', self::getMethod('emitshorteningEvents')->invokeArgs($plugin, array(
            'foo:bar',
            $url,
        )));

        Phake::inOrder(
            Phake::verify($emitter)->listeners($eventName),
            Phake::verify($emitter)->emit($eventName, array($url, $privateDeferred))
        );
    }

    public function testEmitShorteningNone() {
        $loop = Phake::mock('React\EventLoop\LoopInterface');
        $url = 'http://google.com/';
        $logger = Phake::mock('Monolog\Logger');
        $privateDeferred = Phake::mock('React\Promise\Deferred');

        Phake::when($loop)->listeners('url.shorten.google.com')->thenReturn(array());

        $emitter = Phake::mock('Evenement\EventEmitterInterface');
        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        Phake::when($plugin)->preparePromises()->thenReturn(array(
            $privateDeferred,
            Phake::mock('React\Promise\PromiseInterface'),
        ));
        Phake::when($plugin)->setEventEmitter($emitter)->thenCallParent();
        Phake::when($plugin)->setLogger($logger)->thenCallParent();
        Phake::when($plugin)->setLoop($loop)->thenCallParent();

        $plugin->setEventEmitter($emitter);
        $plugin->setLogger($logger);
        $plugin->setLoop($loop);

        $this->assertInstanceOf('React\Promise\PromiseInterface', self::getMethod('emitshorteningEvents')->invokeArgs($plugin, array(
            'foo:bar',
            $url,
        )));

        Phake::inOrder(
            Phake::verify($loop)->addTimer(0.1, $this->isType('callable'))
        );
    }

    public function testCreateRequest() {
        $url = 'http://example.com/';

        $queue = Phake::mock('Phergie\Irc\Bot\React\EventQueue');
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');

        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        $request = self::getMethod('createRequest')->invokeArgs($plugin, array(
            'foo:bar',
            $url,
            $event,
            $queue,
        ));
        $this->assertInstanceOf('Phergie\Plugin\Http\Request', $request);

        Phake::when($plugin)->emitShorteningEvents($this->isType('string'), $this->isType('string'))->thenReturn(new FulfilledPromise($url));

        $body = Phake::mock('GuzzleHttp\Stream\StreamInterface');
        Phake::when($body)->getContents()->thenReturn('');

        $request->callResolve(
            new Response(200, [
                'foo' => 'bar',
            ], $body)
        );

        $request->callResolve(
            new Response(200, [
                'foo' => 'bar',
            ], $body)
        );

        Phake::inOrder(
            Phake::verify($plugin, Phake::times(2))->logDebug($this->isType('string')),
            Phake::verify($plugin, Phake::times(2))->emitShorteningEvents($this->isType('string'), $this->isType('string'))
        );
    }

    public function testHandleUrlUselessUrlProvider() {
        return array(
            array(''),
            array('http://'),
        );
    }

    /**
     * @dataProvider testHandleUrlUselessUrlProvider
     */
    public function testHandleUrlUselessUrl($url) {
        $this->assertNotTrue(self::getMethod('handleUrl')->invokeArgs(Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin'), array(
            $url,
            Phake::mock('Phergie\Irc\Event\UserEvent'),
            Phake::mock('Phergie\Irc\Bot\React\EventQueue'),
        )));
    }

    public function testHandleUrlNoRequest() {
        $url = 'example.com';
        $correctedUrl = 'http://example.com/';

        $queue = Phake::mock('Phergie\Irc\Bot\React\EventQueue');
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');

        $emitter = Phake::mock('Evenement\EventEmitterInterface');

        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        Phake::when($plugin)->setEventEmitter($emitter)->thenCallParent();
        Phake::when($plugin)->emitUrlEvents($this->isType('string'), $correctedUrl, $event, $queue)->thenReturn(false);
        $plugin->setEventEmitter($emitter);


        $this->assertTrue(self::getMethod('handleUrl')->invokeArgs($plugin, array(
            $url,
            $event,
            $queue,
        )));

        Phake::inOrder(
            Phake::verify($plugin)->emitUrlEvents($this->isType('string'), $correctedUrl, $event, $queue),
            Phake::verify($emitter)->emit('url.host.all', array(
                $correctedUrl,
                $event,
                $queue,
            ))
        );
    }

    public function testHandleUrlRequest() {
        $url = 'http://example.com/';

        $queue = Phake::mock('Phergie\Irc\Bot\React\EventQueue');
        $event = Phake::mock('Phergie\Irc\Event\UserEvent');
        $emitter = Phake::mock('Evenement\EventEmitterInterface');
        $request = Phake::mock('Phergie\Plugin\Http\Request');

        $plugin = Phake::mock('Phergie\Irc\Plugin\React\Url\Plugin');
        Phake::when($plugin)->setEventEmitter($emitter)->thenCallParent();
        Phake::when($plugin)->emitUrlEvents($this->isType('string'), $url, $event, $queue)->thenReturn(true);
        Phake::when($plugin)->createRequest($this->isType('string'), $url, $event, $queue)->thenReturn($request);
        $plugin->setEventEmitter($emitter);


        $this->assertTrue(self::getMethod('handleUrl')->invokeArgs($plugin, array(
            $url,
            $event,
            $queue,
        )));

        Phake::inOrder(
            Phake::verify($plugin)->emitUrlEvents($this->isType('string'), $url, $event, $queue),
            Phake::verify($emitter)->emit('http.request', array($request)),
            Phake::verify($emitter)->emit('url.host.all', array(
                $url,
                $event,
                $queue,
            ))
        );
    }

    public function provideFiltered()
    {
        yield [
            '',
            0,
        ];

        yield [
            'http://phergie.org',
            1,
        ];

        yield [
            'http://phergie.org http://wyrihaximus.net',
            2,
        ];
    }

    /**
     * @dataProvider provideFiltered
     */
    public function testFiltered($text, $times)
    {
        $event = Phake::mock(UserEvent::class);
        Phake::when($event)->getParams()->thenReturn([
            'text' => $text,
        ]);
        $queue = Phake::mock(EventQueue::class);
        $filter = Phake::mock(FilterInterface::class);
        Phake::when($filter)->filter($this->isInstanceOf(EventInterface::class))->thenReturn(true);

        (new Plugin([
            'filter' => $filter,
        ]))->handleIrcReceived($event, $queue);

        Phake::verify($filter, Phake::times($times))->filter($this->isInstanceOf(EventInterface::class));
    }
}
