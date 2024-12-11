<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api\Data;

interface CheckoutPaymentTermInterface
{
    /**
     * @return string
     */
    public function getPaymentMethod(): string;

    /**
     * @param string $paymentMethod
     * @return void
     */
    public function setPaymentMethod(string $paymentMethod): void;

    /**
     * @return string
     */
    public function getPaymentTerm(): string;

    /**
     * @param string $paymentTerm
     * @return void
     */
    public function setPaymentTerm(string $paymentTerm): void;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void;

    /**
     * @return string
     */
    public function getDueDate(): string;

    /**
     * @param string $dueDate
     * @return void
     */
    public function setDueDate(string $dueDate): void;
}
