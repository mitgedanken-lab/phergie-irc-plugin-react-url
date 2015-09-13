<?php
/**
 * This file is part of PhergieUrl.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phergie\Plugin\Url;

/**
 * Interface UrlInterface
 *
 * @package Phergie\Plugin\Url
 */
interface UrlInterface
{
    public function getHeaders();

    /**
     * @return integer
     */
    public function getCode();

    /**
     * @return string
     */
    public function getBody();

    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return long
     */
    public function getTiming();
}
