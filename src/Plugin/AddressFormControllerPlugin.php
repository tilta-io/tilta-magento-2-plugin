<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Plugin;

use Magento\Customer\Controller\Address\Form;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\Page;
use Tilta\Payment\Exception\CountryChangeIsNotAllowedException;
use Tilta\Payment\Service\BuyerService;

class AddressFormControllerPlugin
{
    public function __construct(
        private readonly BuyerService $buyerService,
        private readonly ManagerInterface $messageManager
    ) {
    }

    public function afterExecute(Form $subject, ?ResultInterface $result): ?ResultInterface
    {
        if (!$result instanceof Page) {
            return $result;
        }

        $addressId = $subject->getRequest()->getParam('id');
        if (!empty($addressId) && !$this->buyerService->canChangeCountry((int) $addressId)) {
            $this->messageManager->addWarningMessage((new CountryChangeIsNotAllowedException([$addressId]))->getMessage());
        }

        return $result;
    }
}
