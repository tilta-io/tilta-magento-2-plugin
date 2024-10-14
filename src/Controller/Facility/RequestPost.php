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
use Magento\Framework\Controller\ResultFactory;
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
        private readonly ResultFactory $resultFactory
    ) {
        parent::__construct($addressRepository, $customerSession, $request);
    }

    public function execute()
    {
        $address = $this->getAddress();

        $data = [];
        $data[Telephone::ATTRIBUTE_CODE] = $this->request->getParam(Telephone::ATTRIBUTE_CODE, $address->getTelephone());
        if (empty($data[Telephone::ATTRIBUTE_CODE])) {
            $this->messageManager->addErrorMessage((string) __('Please provide your phone number.'));
        }

        $data[CustomerAddressBuyer::LEGAL_FORM] = $this->request->getParam(CustomerAddressBuyer::LEGAL_FORM);
        if (empty($data[CustomerAddressBuyer::LEGAL_FORM])) {
            $this->messageManager->addErrorMessage((string) __('Please provide the legal form.'));
        }

        $data[CustomerAddressBuyer::INCORPORATED_AT] = $this->request->getParam(CustomerAddressBuyer::INCORPORATED_AT);
        $incorporatedAt = $data[CustomerAddressBuyer::INCORPORATED_AT];
        if (!is_array($incorporatedAt) || count($incorporatedAt) !== 3) {
            $this->messageManager->addErrorMessage((string) __('Please provide the date of incorporation.'));
        } else {
            $incorporatedAt = sprintf('%02d-%02d-%02d', $incorporatedAt['year'], $incorporatedAt['month'], $incorporatedAt['day']);
            $data[CustomerAddressBuyer::INCORPORATED_AT] = $incorporatedAt;
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

        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setPath('*/*/list');

        return $redirect;
    }

    private function redirectBackToForm(AddressInterface $address): Redirect
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setPath('*/*/request', [
            'id' => $address->getId(),
        ]);

        return $redirect;
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
