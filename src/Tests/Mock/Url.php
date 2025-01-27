<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Mock;

use Magento\Framework\UrlInterface;

class Url implements UrlInterface
{
    public function getUseSession(): bool
    {
        return false;
    }

    public function getBaseUrl($params = []): string
    {
        return '';
    }

    public function getCurrentUrl(): string
    {
        return '';
    }

    public function getRouteUrl($routePath = null, $routeParams = null): string
    {
        return $routePath . ($routeParams ? '?' . http_build_query($routeParams) : null);
    }

    public function addSessionParam()
    {
    }

    public function addQueryParams(array $data)
    {
    }

    public function setQueryParam($key, $data)
    {
    }

    public function getUrl($routePath = null, $routeParams = null): string
    {
        return $routePath . (is_array($routeParams) ? '?' . http_build_query($routeParams) : null);
    }

    public function escape($value)
    {
        return $value;
    }

    public function getDirectUrl($url, $params = []): string
    {
        return $url . '?' . http_build_query($params);
    }

    public function sessionUrlVar($html)
    {
    }

    public function isOwnOriginUrl(): bool
    {
        return false;
    }

    public function getRedirectUrl($url)
    {
        return $url;
    }

    public function setScope($params)
    {
    }
}
