<?php

namespace Phergie\Irc\Plugin\React\Url\Filter;

use Phergie\Irc\Event\EventInterface;
use Phergie\Irc\Plugin\React\EventFilter\FilterInterface;

class UrlSectionFilter implements FilterInterface
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $section;

    /**
     *
     * @param string $value
     */
    public function __construct($section, $value)
    {
        $this->section = $section;
        $this->value = $value;
    }

    /**
     * @param EventInterface $event
     * @return bool|null
     */
    public function filter(EventInterface $event)
    {
        if (!($event instanceof UrlEvent)) {
            return null;
        }

        $section = $event->getUrlSection($this->section);
        if ($section === false) {
            return null;
        }

        $pattern = '/^' . str_replace('*', '.*', $this->value) . '$/';
        if (preg_match($pattern, $section)) {
            return true;
        }

        return false;
    }
}
