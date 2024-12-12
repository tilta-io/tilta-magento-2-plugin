<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Service;

use DateTime;
use DateTimeInterface;
use Exception;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;
use RuntimeException;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Exception\MissingBuyerInformationException;
use Tilta\Payment\Helper\AmountHelper;
use Tilta\Payment\Model\CustomerAddressBuyer;
use Tilta\Sdk\Exception\GatewayException\Facility\DuplicateFacilityException;
use Tilta\Sdk\Exception\GatewayException\Facility\NoActiveFacilityFoundException;
use Tilta\Sdk\Exception\GatewayException\NotFoundException\BuyerNotFoundException;
use Tilta\Sdk\Exception\TiltaException;
use Tilta\Sdk\Model\Request\Facility\CreateFacilityRequestModel;
use Tilta\Sdk\Model\Request\Facility\GetFacilityRequestModel;
use Tilta\Sdk\Model\Response\Facility;
use Tilta\Sdk\Model\Response\Facility\GetFacilityResponseModel;
use Tilta\Sdk\Service\Request\Facility\CreateFacilityRequest;
use Tilta\Sdk\Service\Request\Facility\GetFacilityRequest;

class FacilityService
{
    public function __construct(
        private readonly RequestServiceFactory $requestServiceFactory,
        private readonly CustomerAddressBuyerRepositoryInterface $repository,
        private readonly BuyerService $buyerService
    ) {
    }

    /**
     * @throws TiltaException
     * @throws MissingBuyerInformationException
     * @throws BuyerNotFoundException
     */
    public function createFacilityForBuyerIfNotExist(AddressInterface $address): GetFacilityResponseModel
    {
        $this->buyerService->upsertBuyer($address);

        // update buyer-data
        $buyerData = $this->repository->getByCustomerAddressId((int) $address->getId());
        $buyerExternalId = $buyerData->getBuyerExternalId();

        if (empty($buyerExternalId)) {
            throw new LocalizedException(__('Invalid buyer-external-id'));
        }

        try {
            $createFacilityRequest = $this->requestServiceFactory->get(CreateFacilityRequest::class);
            $createFacilityRequest->execute(new CreateFacilityRequestModel($buyerExternalId));
        } catch (DuplicateFacilityException) {
            // do nothing. we will fetch the facility
        }

        $facility = $this->getFacility($address);
        if ($facility instanceof Facility) {
            $this->updateFacilityOnCustomerAddress($buyerData, $facility);
            return $facility;
        }

        throw new LocalizedException(__('Facility got not returned from gateway'));
    }

    public function getFacility(AddressInterface $addressEntity): ?GetFacilityResponseModel
    {
        $extension = $addressEntity->getExtensionAttributes();
        if (!$extension) {
            throw new RuntimeException('extension of address has not been loaded');
        }

        $tiltaBuyer = $extension->getTiltaBuyer();
        $buyerExternalId = $tiltaBuyer?->getBuyerExternalId();
        if (empty($buyerExternalId)) {
            return null;
        }

        $request = $this->requestServiceFactory->get(GetFacilityRequest::class);

        try {
            $facility = $request->execute(new GetFacilityRequestModel($buyerExternalId));
            $this->updateFacilityOnCustomerAddress($tiltaBuyer, $facility);

            return $facility;
        } catch (NoActiveFacilityFoundException) {
            return null;
        }
    }

    public function updateFacilityOnCustomerAddress(CustomerAddressBuyerInterface $buyerData, Facility $facility): void
    {
        $buyerData->setFacilityTotalAmount($facility->getTotalAmount());
        $buyerData->setFacilityValidUntil($facility->getExpiresAt()->format($buyerData::DATETIME_FORMAT));

        $this->repository->save($buyerData);
    }

    public function checkCartAmount(AddressInterface $addressEntity, float $totalAmount): bool
    {
        $extension = $addressEntity->getExtensionAttributes();
        if (!$extension) {
            throw new RuntimeException('extension of address has not been loaded');
        }

        $tiltaData = $extension->getTiltaBuyer();

        if (!$tiltaData instanceof CustomerAddressBuyer) {
            // facility is "valid" if tiltaData does not exist - customer may create a buyer.
            return true;
        }

        $validUntil = DateTime::createFromFormat($tiltaData::DATETIME_FORMAT, (string) $tiltaData->getFacilityValidUntil());
        if (!$validUntil instanceof DateTimeInterface) {
            // facility seems to be invalid or has been never requested
            return false;
        }

        $facility = null;
        if ($validUntil->getTimestamp() < time()) {
            try {
                // fetching facility will update the facility in the database
                $facility = $this->getFacility($addressEntity);
            } catch (Exception) {
                // error during facility fetching -> facility seems to be not valid
                return false;
            }
        }

        return ($facility?->getTotalAmount() ?? $tiltaData->getFacilityTotalAmount()) >= AmountHelper::toSdk($totalAmount);
    }
}
