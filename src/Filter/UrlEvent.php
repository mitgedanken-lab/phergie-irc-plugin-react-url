<?php

namespace Phergie\Irc\Plugin\React\Url\Filter;

use Phergie\Irc\ConnectionInterface;
use Phergie\Irc\Event\EventInterface;

class UrlEvent implements EventInterface
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array|mixed|false
     */
    protected $parsedUrl;

    /**
     * @var EventInterface
     */
    protected $event;

    /**
     * UrlEvent constructor.
     * @param string $url
     * @param EventInterface $event
     */
    public function __construct($url, EventInterface $event)
    {
        $this->url = $url;
        $this->parsedUrl = parse_url($url);
        $this->event = $event;
    }
    
    public function setMessage($message)
    {
        return $this->event->setMessage($message);
    }

    public function getMessage()
    {
        return $this->event->getMessage();
    }

    public function setConnection(ConnectionInterface $connection)
    {
        return $this->event->setConnection($connection);
    }

    public function getConnection()
    {
        return $this->event->getConnection();
    }

    public function setParams(array $params)
    {
        return $this->event->setParams($params);
    }

    public function getParams()
    {
        return $this->event->getParams();
    }

    public function setCommand($command)
    {
        return $this->event->setCommand($command);
    }

    public function getCommand()
    {
        return $this->event->getCommand();
    }

    public function getUrlSection($section)
    {
        if (isset($this->parsedUrl[$section])) {
            return $this->parsedUrl[$section];
        }

        return null;
    }
}
