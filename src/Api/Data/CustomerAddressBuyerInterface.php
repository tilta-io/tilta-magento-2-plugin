<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api\Data;

interface CustomerAddressBuyerInterface
{
    /**
     * @var string
     */
    public const DATE_FORMAT = 'Y-m-d';

    /**
     * @var string
     */
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var string
     */
    public const CUSTOMER_ADDRESS_ID = 'customer_address_id';

    /**
     * @var string
     */
    public const LEGAL_FORM = 'legal_form';

    /**
     * @var string
     */
    public const BUYER_EXTERNAL_ID = 'buyer_external_id';

    /**
     * @var string
     */
    public const INCORPORATED_AT = 'incorporated_at';

    /**
     * @var string
     */
    public const FACILITY_TOTAL_AMOUNT = 'facility_total_amount';

    /**
     * @var string
     */
    public const FACILITY_VALID_UNTIL = 'facility_valid_until';

    /**
     * @var string
     */
    public const CREATED_AT = 'created_at';

    /**
     * @var string
     */
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int
     */
    public function getCustomerAddressId(): int;

    /**
     * @param int $customerAddressId
     * @return self
     */
    public function setCustomerAddressId(int $customerAddressId): self;

    /**
     * @return string
     */
    public function getLegalForm(): string;

    /**
     * @param string $legalForm
     * @return self
     */
    public function setLegalForm(string $legalForm): self;

    /**
     * @return string|null
     */
    public function getBuyerExternalId(): ?string;

    /**
     * @param string|null $buyerExternalId
     * @return self
     */
    public function setBuyerExternalId(?string $buyerExternalId = null): self;

    /**
     * @return string|null
     */
    public function getIncorporatedAt(): ?string;

    /**
     * @param string $incorporatedAt
     * @return self
     */
    public function setIncorporatedAt(string $incorporatedAt): self;

    /**
     * @return int|null
     */
    public function getFacilityTotalAmount(): ?int;

    /**
     * @param int|null $totalAmount
     * @return self
     */
    public function setFacilityTotalAmount(?int $totalAmount): self;

    /**
     * @return string|null
     */
    public function getFacilityValidUntil(): ?string;

    /**
     * @param string|null $validUntil
     * @return self
     */
    public function setFacilityValidUntil(?string $validUntil): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string|null $updatedAt
     * @return self
     */
    public function setUpdatedAt(string|null $updatedAt): self;
}
