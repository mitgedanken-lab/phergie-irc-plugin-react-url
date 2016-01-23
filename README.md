# Url Plugin

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for Display URL information about links.

[![Build Status](https://secure.travis-ci.org/phergie/phergie-irc-plugin-react-url.png?branch=master)](http://travis-ci.org/phergie/phergie-irc-plugin-react-url)

## Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require phergie/phergie-irc-plugin-react-url 
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
return array(

    'plugins' => array(

        // dependencies
        new \Phergie\Plugin\Dns\Plugin, // Handles DNS lookups for the HTTP plugin
        new \Phergie\Plugin\Http\Plugin, // Handles the HTTP requests for this plugin

        // configuration
        new \Phergie\Irc\Plugin\React\Url\Plugin(array(
            // All configuration is optional
            
            'hostUrlEmitsOnly' => false, // url.host.(all|<host>) emits only, no further URL handling / shortening
            
            // or

            'handler' => new \Phergie\Irc\Plugin\React\Url\DefaultUrlHandler(), // URL handler that creates a formatted message based on the URL

            // or

            'shortenTimeout' => 15 // If after this amount of seconds no url shortener has come up with a short URL the normal URL will be used. (Not in effect when there are no shorteners listening.)

            // or

            'filter' => null // Any valid filter implementing Phergie\Irc\Plugin\React\EventFilter\FilterInterface to filter which messages should be handled 

        )),

    )
);
```

## Events

This plugin emits the following generic, do what ever you want with it, events.

* `url.host.HOSTNAME` For example `url.host.twitter.com` (`www.` is stripped from the hostname).
* `url.host.all` For all hostnames.

This plugins also emits two events for url shortening. Only called when there are listeners registered. Each event emit is passed a `UrlshorteningEvent`, if a shortener resolved short url it calls the `resolve` method on the promise.

* `url.shorten.HOSTNAME` For example `url.shorten.twitter.com` (`www.` is stripped from the hostname).
* `url.shorten.all` For all hostnames.

## Placeholders

The following placeholders can be used to compose a message that is passed as the first argument for `DefaultUrlHandler` to create custom messages:

* `%url%` - Full URL
* `%url-short%` - Shortened URL
* `%http-status-code%` - HTTP status code
* `%timing%` - Time in seconds it took for th request to complete
* `%timing2%` - Time in seconds it took for th request to complete rounded off to a maximum of two decimals
* `%response-time%` - Time in seconds it took for th request to complete
* `%response-time2%` - Time in seconds it took for th request to complete rounded off to a maximum of two decimals
* `%title%` - Page title
* `%composed-title%` - Page title

### Header Placeholders

Selection of response headers from: [en.wikipedia.org/wiki/List_of_HTTP_header_fields#Response_Headers](http://en.wikipedia.org/wiki/List_of_HTTP_header_fields#Response_Headers)

* `%header-age%`
* `%header-content-type%`
* `%header-content-length%`
* `%header-content-language%`
* `%header-date%`
* `%header-etag%`
* `%header-expires%`
* `%header-last-modified%`
* `%header-server%`
* `%header-x-powered-by%`

## UrlSectionFilter

This plugin comes with the `UrlSectionFilter` that lets you filter on the different key value pairs coming out of [`parse_url`](http://php.net/parse_url). The following example filter allows `www.phergie.org`, `www2.phergie.org`, and `phergie.org`:

```php
new OrFilter([
    new UrlSectionFilter('host', '*.phergie.org'),
    new UrlSectionFilter('host', 'phergie.org'),
])
``

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit
```

## License

Released under the MIT License. See `LICENSE`.
