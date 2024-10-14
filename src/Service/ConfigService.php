<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Service;

use LogicException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigService
{
    private const CONFIG_ENABLED = 'payment/tilta/enabled';

    private const CONFIG_SANDBOX_ENABLED = 'payment/tilta/sandbox_enabled';

    private const CONFIG_TOKEN_SANDBOX = 'payment/tilta/sandbox/auth_token';

    private const CONFIG_TOKEN_LIVE = 'payment/tilta/production/auth_token';

    private const CONFIG_MERCHANT_EXTERNAL_ID_SANDBOX = 'payment/tilta/sandbox/merchant_external_id';

    private const CONFIG_MERCHANT_EXTERNAL_ID_LIVE = 'payment/tilta/production/merchant_external_id';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isConfigReady(): bool
    {
        if (!$this->scopeConfig->isSetFlag(self::CONFIG_ENABLED, ScopeInterface::SCOPE_WEBSITE)) {
            return false;
        }

        if ($this->scopeConfig->isSetFlag(self::CONFIG_SANDBOX_ENABLED, ScopeInterface::SCOPE_WEBSITE)) {
            $authToken = $this->scopeConfig->getValue(self::CONFIG_TOKEN_SANDBOX, ScopeInterface::SCOPE_WEBSITE);
            $merchantExternalId = $this->scopeConfig->getValue(self::CONFIG_MERCHANT_EXTERNAL_ID_SANDBOX, ScopeInterface::SCOPE_WEBSITE);
        } else {
            $authToken = $this->scopeConfig->getValue(self::CONFIG_TOKEN_LIVE, ScopeInterface::SCOPE_WEBSITE);
            $merchantExternalId = $this->scopeConfig->getValue(self::CONFIG_MERCHANT_EXTERNAL_ID_LIVE, ScopeInterface::SCOPE_WEBSITE);
        }

        return !empty($authToken) && !empty($merchantExternalId);
    }

    public function isSandboxEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_SANDBOX_ENABLED);
    }

    public function getMerchantExternalId(): string
    {
        if (!$this->isConfigReady()) {
            throw new LogicException('Tilta configuration has not been set properly.');
        }

        if ($this->isSandboxEnabled()) {
            return (string) $this->scopeConfig->getValue(self::CONFIG_MERCHANT_EXTERNAL_ID_SANDBOX, ScopeInterface::SCOPE_WEBSITE);
        }

        return (string) $this->scopeConfig->getValue(self::CONFIG_MERCHANT_EXTERNAL_ID_LIVE, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getToken(): string
    {
        if (!$this->isConfigReady()) {
            throw new LogicException('Tilta configuration has not been set properly.');
        }

        if ($this->isSandboxEnabled()) {
            return (string) $this->scopeConfig->getValue(self::CONFIG_TOKEN_SANDBOX, ScopeInterface::SCOPE_WEBSITE);
        }

        return (string) $this->scopeConfig->getValue(self::CONFIG_TOKEN_LIVE, ScopeInterface::SCOPE_WEBSITE);
    }
}
