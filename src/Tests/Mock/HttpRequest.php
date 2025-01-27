<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Mock;

use Magento\Framework\App\RequestInterface;

class HttpRequest implements RequestInterface
{
    private array $params = [];

    public function getModuleName(): string
    {
        return 'test';
    }

    public function setModuleName($name)
    {
    }

    public function getActionName(): string
    {
        return 'test';
    }

    public function setActionName($name)
    {
    }

    public function getParam($key, $defaultValue = null)
    {
        return $this->params[$key] ?? $defaultValue;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getCookie($name, $default)
    {
        return null;
    }

    public function isSecure(): bool
    {
        return true;
    }

    public function setDispatched($flag = true): bool
    {
        return false;
    }
}
