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
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Widget\Telephone;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use RuntimeException;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Exception\MissingBuyerInformationException;
use Tilta\Payment\Model\CustomerAddressBuyer;
use Tilta\Sdk\Exception\GatewayException\NotFoundException\BuyerNotFoundException;
use Tilta\Sdk\Exception\TiltaException;
use Tilta\Sdk\Model\Address;
use Tilta\Sdk\Model\Buyer;
use Tilta\Sdk\Model\ContactPerson;
use Tilta\Sdk\Model\Request\Buyer\CreateBuyerRequestModel;
use Tilta\Sdk\Model\Request\Buyer\GetBuyerDetailsRequestModel;
use Tilta\Sdk\Model\Request\Buyer\UpdateBuyerRequestModel;
use Tilta\Sdk\Service\Request\Buyer\CreateBuyerRequest;
use Tilta\Sdk\Service\Request\Buyer\GetBuyerDetailsRequest;
use Tilta\Sdk\Service\Request\Buyer\UpdateBuyerRequest;
use Tilta\Sdk\Util\AddressHelper;

class BuyerService
{
    public function __construct(
        private readonly AddressRepositoryInterface $customerAddressRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ObjectManagerInterface $objectManager,
        private readonly ManagerInterface $eventManager,
        private readonly RequestServiceFactory $requestServiceFactory,
        private readonly CustomerAddressBuyerRepositoryInterface $repository,
        private readonly ConfigService $config
    ) {
    }

    /**
     * @return string|null the save buyer external id within the customer address
     * this method will not generate a buyer external id, if no buyer external ID has been set up.
     * Please use method `generateBuyerExternalId` to generate a buyer-external-id
     */
    public static function getBuyerExternalId(AddressInterface $address): ?string
    {
        $tiltaData = $address->getExtensionAttributes()?->getTiltaBuyer();

        return $tiltaData?->getBuyerExternalId();
    }

    public function generateBuyerExternalId(AddressInterface $address): string
    {
        $externalId = static::getBuyerExternalId($address);

        if (!empty($externalId)) {
            return $externalId;
        }

        return $this->config->getBuyerExternalIdPrefix() . ($address->getCustomerId() . '-' . $address->getId());
    }

    public function updateCustomerAddressData(AddressInterface $addressEntity, array $data): void
    {
        if (is_string($data[Telephone::ATTRIBUTE_CODE] ?? null)) {
            $addressEntity->setTelephone($data[Telephone::ATTRIBUTE_CODE]);
        }

        //        TODO verify if still necessary
        //        if (isset($data['salutation'])) {
        //            $addressEntity->setTelephone($data['salutation']);
        //        }

        $buyerData = $addressEntity->getExtensionAttributes()?->getTiltaBuyer() ?: $this->createNewCustomerAddressBuyerInstance($addressEntity);

        $incorporatedAtRaw = $data[CustomerAddressBuyerInterface::INCORPORATED_AT] ?? null;
        if (is_string($incorporatedAtRaw) && preg_match('#^\d{4}-\d{2}-\d{2}$#', $incorporatedAtRaw)) {
            $incorporatedAt = DateTime::createFromFormat('Y-m-d', $incorporatedAtRaw);
            if ($incorporatedAt === false) {
                throw new LocalizedException(__('Invalid date format'));
            }
        } elseif ($incorporatedAtRaw instanceof DateTimeInterface) {
            $incorporatedAt = $data['incorporatedAt'];
        } else {
            throw new LocalizedException(__('Invalid date format'));
        }

        $buyerData->setIncorporatedAt($incorporatedAt->format($buyerData::DATE_FORMAT));

        if (is_string($data[CustomerAddressBuyerInterface::LEGAL_FORM] ?? null)) {
            $buyerData->setLegalForm($data[CustomerAddressBuyerInterface::LEGAL_FORM]);
        }

        $this->repository->save($buyerData);

        $this->customerAddressRepository->save($addressEntity);
    }

    private function createNewCustomerAddressBuyerInstance(AddressInterface $addressEntity): CustomerAddressBuyerInterface
    {
        $extension = $addressEntity->getExtensionAttributes();
        if (!$extension) {
            throw new RuntimeException('extension of address has not been loaded');
        }

        $buyerData = $extension->getTiltaBuyer() ?: $this->objectManager->create(CustomerAddressBuyerInterface::class);
        $extension->setTiltaBuyer($buyerData);
        $buyerData->setCustomerAddressId((int) $addressEntity->getId());
        $buyerData->setBuyerExternalId($this->generateBuyerExternalId($addressEntity));

        return $buyerData;
    }

    /**
     * @throws TiltaException
     */
    public function upsertBuyer(AddressInterface $addressEntity): Buyer
    {
        $this->validateAdditionalData($addressEntity);

        $buyerExternalId = $this->generateBuyerExternalId($addressEntity);

        try {
            $buyerRequest = $this->requestServiceFactory->get(GetBuyerDetailsRequest::class);
            $buyer = $buyerRequest->execute(new GetBuyerDetailsRequestModel($buyerExternalId));
            $this->updateBuyer($addressEntity);
        } catch (BuyerNotFoundException) {
            $buyerRequestModel = $this->createCreateBuyerRequestModel($addressEntity);

            $createBuyerRequest = $this->requestServiceFactory->get(CreateBuyerRequest::class);
            $createBuyerRequest->execute($buyerRequestModel);

            $buyer = $buyerRequestModel;
        }

        $buyerData = $this->createNewCustomerAddressBuyerInstance($addressEntity);
        if ($buyer->getIncorporatedAt()) {
            $buyerData->setIncorporatedAt($buyer->getIncorporatedAt()->format($buyerData::DATE_FORMAT));
        }

        $buyerData->setLegalForm((string) $buyer->getLegalForm());

        $this->repository->save($buyerData);

        return $buyer;
    }

    /**
     * @throws MissingBuyerInformationException
     */
    private function updateBuyer(AddressInterface $address): void
    {
        $buyerRequestModel = $this->createUpdateBuyerRequestModel($address);

        $requestService = $this->requestServiceFactory->get(UpdateBuyerRequest::class);

        $requestService->execute($buyerRequestModel);
    }

    /**
     * @throws MissingBuyerInformationException
     */
    private function createCreateBuyerRequestModel(AddressInterface $address): CreateBuyerRequestModel
    {
        return $this->getRequestModel(CreateBuyerRequestModel::class, $address, 'tilta_create_buyer_request_built');
    }

    /**
     * @throws MissingBuyerInformationException
     */
    private function createUpdateBuyerRequestModel(AddressInterface $address): UpdateBuyerRequestModel
    {
        return $this->getRequestModel(UpdateBuyerRequestModel::class, $address, 'tilta_update_buyer_request_built');
    }

    private function validateAdditionalData(AddressInterface $addressEntity): void
    {
        $extension = $addressEntity->getExtensionAttributes();
        if (!$extension) {
            throw new RuntimeException('extension of address has not been loaded');
        }

        $errors = [];

        // TODO check if it is still required
        //        if (empty($address->getPrefix())) {
        //            $errors[] = __('Please provide your salutation.');
        //        }

        if (empty($addressEntity->getTelephone())) {
            $errors[] = __('Please provide your phone number.');
        }

        if (empty($addressEntity->getCompany())) {
            $errors[] = __('Please provide the company name.');
        }

        $tiltaData = $extension->getTiltaBuyer();

        if (empty($tiltaData?->getIncorporatedAt())) {
            $errors[] = __('Please provide the date of incorporation.');
        }

        if (empty($tiltaData?->getLegalForm())) {
            $errors[] = __('Please provide the legal form.');
        }

        if ($errors !== []) {
            throw new MissingBuyerInformationException($errors);
        }
    }

    /**
     * @template T of CreateBuyerRequestModel|UpdateBuyerRequestModel
     * @param class-string<T> $class
     * @return T
     * @throws MissingBuyerInformationException
     */
    private function getRequestModel(string $class, AddressInterface $addressEntity, string $eventName)
    {
        $extension = $addressEntity->getExtensionAttributes();
        if (!$extension) {
            throw new RuntimeException('extension of address has not been loaded');
        }

        $this->validateAdditionalData($addressEntity);

        /** @var CustomerAddressBuyer $tiltaData */ // is never null, cause has been already validated in `validateAdditionalData`
        $tiltaData = $extension->getTiltaBuyer();

        $customer = $this->customerRepository->getById((int) $addressEntity->getCustomerId());

        $buyerExternalId = $this->generateBuyerExternalId($addressEntity);
        /** @var T $requestModel */
        $requestModel = match ($class) {
            CreateBuyerRequestModel::class => (new CreateBuyerRequestModel())->setExternalId($buyerExternalId),
            UpdateBuyerRequestModel::class => new UpdateBuyerRequestModel($buyerExternalId),
            default => throw new RuntimeException('invalid request data class'),
        };

        $requestModel
            ->setLegalName($addressEntity->getCompany() ?: '')
            ->setTradingName($addressEntity->getCompany())
            ->setLegalForm($tiltaData->getLegalForm())
            ->setBusinessAddress($this->createTiltaAddressFromEntity($addressEntity));

        $registeredAt = null;
        if (!empty($customer->getCreatedAt())) {
            $registeredAt = DateTime::createFromFormat('Y-m-d H:i:s', $customer->getCreatedAt());
        }

        $requestModel->setRegisteredAt($registeredAt ?: new DateTime());

        if (!empty($tiltaData->getIncorporatedAt())) {
            $incorporatedAt = DateTime::createFromFormat($tiltaData::DATE_FORMAT, $tiltaData->getIncorporatedAt());
            if (!$incorporatedAt instanceof DateTimeInterface) {
                throw new LocalizedException(__('Incorporated at must be a valid date'));
            }

            $requestModel->setIncorporatedAt($incorporatedAt);
        }

        $requestModel->setContactPersons([
            (new ContactPerson())
                // TODO check if it is still required
//                ->setSalutation($this->getMappedSalutationFromAddress($addressEntity))
                ->setFirstName($addressEntity->getFirstname())
                ->setLastName($addressEntity->getLastname())
                ->setEmail($customer->getEmail())
                ->setPhone($addressEntity->getTelephone())
                ->setAddress($requestModel->getBusinessAddress())
                ->setBirthDate(empty($customer->getDob()) ? null : (DateTime::createFromFormat('Y-m-d', $customer->getDob()) ?: null)),
        ]);

        $this->eventManager->dispatch($eventName, [
            'customer' => $customer,
            'address' => $addressEntity,
            'request_model' => $requestModel,
        ]);

        return $requestModel;
    }

    private function createTiltaAddressFromEntity(AddressInterface $addressEntity): Address
    {
        return (new Address())
            ->setStreet(AddressHelper::getStreetName($addressEntity->getStreet()[0] ?? '') ?: '')
            ->setHouseNumber(AddressHelper::getHouseNumber($addressEntity->getStreet()[0] ?? '') ?: '')
            ->setPostcode($addressEntity->getPostcode() ?: '')
            ->setCity($addressEntity->getCity() ?: '')
            ->setCountry($addressEntity->getCountryId() ?: '')
            ->setAdditional(self::mergeAdditionalAddressLines($addressEntity));
    }

    private function mergeAdditionalAddressLines(AddressInterface $addressEntity): ?string
    {
        $streetLines = $addressEntity->getStreet() ?: [];
        unset($streetLines[0]);

        $streetLines = array_map('trim', $streetLines);
        $additionalLines = array_filter($streetLines, static fn ($value): bool => !empty($value));

        return $additionalLines !== [] ? implode("\n", $additionalLines) : null;
    }

    public function canChangeCountry(int $customerAddressId): bool
    {
        try {
            $buyerData = $this->repository->getByCustomerAddressId((int) $customerAddressId);
            if (!$buyerData->getFacilityValidUntil()) {
                return true;
            }
        } catch (NoSuchEntityException) {
            return true;
        }

        return false;
    }
}
