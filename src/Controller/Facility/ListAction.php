<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Controller\Facility;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Tilta\Payment\Service\ConfigService;

class ListAction implements HttpGetActionInterface, AccountInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly ResultFactory $resultFactory,
        private readonly ConfigService $configService
    ) {
    }

    public function execute()
    {
        if (!$this->configService->isConfigReady()) {
            /** @var Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setPath('customer/account');

            return $redirect;
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend((string) __('Credit facilities'));

        return $page;
    }
}
