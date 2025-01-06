<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model;

use DateTime;
use InvalidArgumentException;
use Magento\Framework\Model\AbstractModel;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;

class CustomerAddressBuyer extends AbstractModel implements CustomerAddressBuyerInterface
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel\CustomerAddressBuyer::class);
    }

    public function getCustomerAddressId(): int
    {
        return (int) $this->getData(self::CUSTOMER_ADDRESS_ID);
    }

    public function setCustomerAddressId(int $customerAddressId): CustomerAddressBuyerInterface
    {
        return $this->setData(self::CUSTOMER_ADDRESS_ID, $customerAddressId);
    }

    public function getLegalForm(): string
    {
        return $this->getData(self::LEGAL_FORM);
    }

    public function setLegalForm(string $legalForm): CustomerAddressBuyerInterface
    {
        return $this->setData(self::LEGAL_FORM, $legalForm);
    }

    public function getBuyerExternalId(): ?string
    {
        return $this->getData(self::BUYER_EXTERNAL_ID);
    }

    public function setBuyerExternalId(?string $buyerExternalId = null): CustomerAddressBuyerInterface
    {
        return $this->setData(self::BUYER_EXTERNAL_ID, $buyerExternalId);
    }

    public function getIncorporatedAt(): ?string
    {
        return $this->getData(self::INCORPORATED_AT);
    }

    public function setIncorporatedAt(?string $incorporatedAt = null): CustomerAddressBuyerInterface
    {
        if (!empty($incorporatedAt) && !DateTime::createFromFormat(self::DATE_FORMAT, $incorporatedAt)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid date (%s)', self::INCORPORATED_AT, self::DATE_FORMAT));
        }

        return $this->setData(self::INCORPORATED_AT, $incorporatedAt);
    }

    public function getFacilityTotalAmount(): ?int
    {
        $value = $this->getData(self::FACILITY_TOTAL_AMOUNT);

        return is_numeric($value) ? (int) $value : null;
    }

    public function setFacilityTotalAmount(?int $totalAmount): CustomerAddressBuyerInterface
    {
        return $this->setData(self::FACILITY_TOTAL_AMOUNT, $totalAmount);
    }

    public function getFacilityValidUntil(): ?string
    {
        return $this->getData(self::FACILITY_VALID_UNTIL);
    }

    public function setFacilityValidUntil(?string $validUntil): CustomerAddressBuyerInterface
    {
        if (!empty($validUntil) && !DateTime::createFromFormat(self::DATETIME_FORMAT, $validUntil)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid date (%s)', self::FACILITY_VALID_UNTIL, self::DATETIME_FORMAT));
        }

        return $this->setData(self::FACILITY_VALID_UNTIL, $validUntil);
    }

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): CustomerAddressBuyerInterface
    {
        if (!empty($createdAt) && !DateTime::createFromFormat(self::DATETIME_FORMAT, $createdAt)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid date (%s)', self::CREATED_AT, self::DATETIME_FORMAT));
        }

        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(?string $updatedAt): CustomerAddressBuyerInterface
    {
        if (!empty($updatedAt) && !DateTime::createFromFormat(self::DATETIME_FORMAT, $updatedAt)) {
            throw new InvalidArgumentException(sprintf('%s must be a valid date (%s)', self::UPDATED_AT, self::DATETIME_FORMAT));
        }

        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
