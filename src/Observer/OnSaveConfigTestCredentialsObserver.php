<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Tilta\Payment\Service\ConfigService;
use Tilta\Payment\Service\RequestServiceFactory;
use Tilta\Sdk\Exception\TiltaException;
use Tilta\Sdk\Model\Request\Order\GetOrderListRequestModel;
use Tilta\Sdk\Service\Request\Order\GetOrderListRequest;

class OnSaveConfigTestCredentialsObserver implements ObserverInterface
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly RequestServiceFactory $requestServiceFactory,
        private readonly ManagerInterface $messageManager,
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->configService->isConfigReady()) {
            return;
        }

        try {
            $this->requestServiceFactory->get(GetOrderListRequest::class)->execute(
                (new GetOrderListRequestModel())
                    ->setMerchantExternalId($this->configService->getMerchantExternalId())
                    ->setLimit(1)
            );
        } catch (TiltaException $tiltaException) {
            throw new LocalizedException(__('Invalid credentials for Tilta Payments. Error Message: %1', $tiltaException->getMessage()), $tiltaException, $tiltaException->getCode());
        }

        $this->messageManager->addSuccessMessage((string) __('Tilta credentials has been verified successfully.'));
    }
}
