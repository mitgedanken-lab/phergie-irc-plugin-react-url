<?php
/**
 * This file is part of PhergieUrl.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phergie\Irc\Plugin\React\Url\Mime;

use Phergie\Irc\Plugin\React\Url\UrlInterface;

/**
 * Interface MimeInterface
 *
 * @package Phergie\Irc\Plugin\React\Url\Mime
 */
interface MimeInterface
{
    /**
     * Return whether this mimetype is supported by this handler.
     *
     * @param string $mimeType The mimetype to check.
     *
     * @return boolean
     */
    public function matches($mimeType);

    /**
     * Extract all possible useful information from the given url.
     *
     * @param array        $replacements Message replacements.
     * @param UrlInterface $url          URL to extract data from.
     *
     * @return array
     */
    public function extract(array $replacements, UrlInterface $url);
}
