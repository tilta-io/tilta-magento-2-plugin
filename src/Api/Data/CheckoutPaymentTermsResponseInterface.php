<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api\Data;

interface CheckoutPaymentTermsResponseInterface
{
    /**
     * @return \Tilta\Payment\Api\Data\CheckoutPaymentTermInterface[]
     */
    public function getPaymentTerms(): array;

    /**
     * @param \Tilta\Payment\Api\Data\CheckoutPaymentTermInterface[] $paymentTerms
     * @return void
     */
    public function setPaymentTerms(array $paymentTerms): void;

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * @param string|null $message
     * @return void
     */
    public function setErrorMessage(?string $message): void;

    /**
     * @param bool $allowCreateFacility
     * @return void
     */
    public function setAllowCreateFacility(bool $allowCreateFacility): void;

    /**
     * @return bool
     */
    public function isAllowCreateFacility(): bool;
}
