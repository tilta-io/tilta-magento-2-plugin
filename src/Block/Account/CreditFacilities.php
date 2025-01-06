<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Block\Account;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Account\SortLink;
use Magento\Customer\Model\Session;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template\Context;
use Tilta\Payment\Service\ConfigService;

class CreditFacilities extends SortLink
{
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        private readonly Session $customerSession,
        private readonly ConfigService $configService,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    protected function _toHtml(): string
    {
        if (!$this->configService->isConfigReady()) {
            return '';
        }

        /** @var AddressInterface $address */
        foreach ($this->customerSession->getCustomer()->getAddresses() as $address) {
            if (!empty($address->getCompany())) {
                return parent::_toHtml();
            }
        }

        return '';
    }
}
