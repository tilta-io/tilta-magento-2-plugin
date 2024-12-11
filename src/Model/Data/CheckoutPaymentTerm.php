<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model\Data;

use Tilta\Payment\Api\Data\CheckoutPaymentTermInterface;

class CheckoutPaymentTerm implements CheckoutPaymentTermInterface
{
    private string $paymentMethod;

    private string $paymentTerm;

    private string $name;

    private string $dueDate;

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPaymentTerm(): string
    {
        return $this->paymentTerm;
    }

    public function setPaymentTerm(string $paymentTerm): void
    {
        $this->paymentTerm = $paymentTerm;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function setDueDate(string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }
}
