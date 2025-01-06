<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Api\Data\CreditFacilityRequest;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getTelephone(): string;

    /**
     * @param string $telephone
     * @return void
     */
    public function setTelephone(string $telephone): void;

    /**
     * @return string
     */
    public function getLegalForm(): string;

    /**
     * @param string $legalForm
     * @return void
     */
    public function setLegalForm(string $legalForm): void;

    /**
     * @return string
     */
    public function getIncorporationDate(): string;

    /**
     * @param string $incorporationDate
     * @return void
     */
    public function setIncorporationDate(string $incorporationDate): void;
}
