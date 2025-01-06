<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model\Data;

use Tilta\Payment\Api\Data\CheckoutPaymentTermsResponseInterface;

class CheckoutPaymentTermsResponse implements CheckoutPaymentTermsResponseInterface
{
    private array $paymentTerms = [];

    private ?string $errorMessage = null;

    private bool $isAllowCreateFacility = false;

    public function getPaymentTerms(): array
    {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(array $paymentTerms): void
    {
        $this->paymentTerms = $paymentTerms;
    }

    public function setErrorMessage(string|null $message): void
    {
        $this->errorMessage = $message;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function isAllowCreateFacility(): bool
    {
        return $this->isAllowCreateFacility;
    }

    public function setAllowCreateFacility(bool $allowCreateFacility): void
    {
        $this->isAllowCreateFacility = $allowCreateFacility;
    }
}
