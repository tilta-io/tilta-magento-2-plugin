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
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Widget\Telephone;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Tilta\Payment\Exception\MissingBuyerInformationException;
use Tilta\Payment\Model\CustomerAddressBuyer;
use Tilta\Payment\Service\BuyerService;
use Tilta\Payment\Service\FacilityService;
use Tilta\Sdk\Exception\TiltaException;

class RequestPost extends AbstractFacility implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        Session $customerSession,
        RequestInterface $request,
        private readonly ManagerInterface $messageManager,
        private readonly BuyerService $buyerService,
        private readonly FacilityService $facilityService,
        private readonly RedirectFactory $redirectFactory
    ) {
        parent::__construct($addressRepository, $customerSession, $request);
    }

    public function execute()
    {
        $address = $this->getAddress();
        $isValid = true;

        $data = [];
        $data[Telephone::ATTRIBUTE_CODE] = $this->request->getParam(Telephone::ATTRIBUTE_CODE, $address->getTelephone());
        if (empty($data[Telephone::ATTRIBUTE_CODE])) {
            $this->messageManager->addErrorMessage((string) __('Please provide your phone number.'));
            $isValid = false;
        }

        $data[CustomerAddressBuyer::LEGAL_FORM] = $this->request->getParam(CustomerAddressBuyer::LEGAL_FORM);
        if (empty($data[CustomerAddressBuyer::LEGAL_FORM])) {
            $this->messageManager->addErrorMessage((string) __('Please provide the legal form.'));
            $isValid = false;
        }

        $data[CustomerAddressBuyer::INCORPORATED_AT] = $this->request->getParam(CustomerAddressBuyer::INCORPORATED_AT);
        $incorporatedAt = $data[CustomerAddressBuyer::INCORPORATED_AT];
        if (is_string($incorporatedAt) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $incorporatedAt)) {
            $data[CustomerAddressBuyer::INCORPORATED_AT] = $incorporatedAt;
        } elseif (!is_array($incorporatedAt) || count($incorporatedAt) !== 3 || !isset($incorporatedAt['year'], $incorporatedAt['month'], $incorporatedAt['day'])) {
            $this->messageManager->addErrorMessage((string) __('Please provide the date of incorporation.'));
            $isValid = false;
        } else {
            $incorporatedAt = sprintf('%02d-%02d-%02d', $incorporatedAt['year'], $incorporatedAt['month'], $incorporatedAt['day']);
            $data[CustomerAddressBuyer::INCORPORATED_AT] = $incorporatedAt;
        }

        if (!$isValid) {
            return $this->redirectBackToForm($address);
        }

        $this->buyerService->updateCustomerAddressData($address, $data);

        try {
            $this->facilityService->createFacilityForBuyerIfNotExist($this->getAddress());
        } catch (MissingBuyerInformationException $missingBuyerInformationException) {
            foreach ($missingBuyerInformationException->getErrorMessages() as $message) {
                $this->messageManager->addErrorMessage($message);
            }

            return $this->redirectBackToForm($address);
        } catch (TiltaException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $this->redirectBackToForm($address);
        }

        $this->messageManager->addSuccessMessage((string) __('The credit facility has been created successful.'));

        return $this->redirectFactory->create()->setPath('*/*/list');
    }

    private function redirectBackToForm(AddressInterface $address): Redirect
    {
        return $this->redirectFactory->create()->setPath('*/*/request', [
            'id' => $address->getId(),
        ]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
