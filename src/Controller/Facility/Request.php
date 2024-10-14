<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Controller\Facility;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Result\PageFactory;
use Tilta\Payment\Helper\Context;

class Request extends AbstractFacility implements HttpGetActionInterface
{
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        Session $customerSession,
        RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly Context $context
    ) {
        parent::__construct($addressRepository, $customerSession, $request);
    }

    public function execute()
    {
        $address = $this->getAddress();

        $this->context->setCurrentEditAddress($address);

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend((string) __('Request credit facility'));

        return $page;
    }
}
