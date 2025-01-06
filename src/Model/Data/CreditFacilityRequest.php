<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model\Data;

use Tilta\Payment\Api\Data\CreditFacilityRequest\RequestInterface;

class CreditFacilityRequest implements RequestInterface
{
    private string $telephone;

    private string $legalForm;

    private string $incorporationDate;

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getLegalForm(): string
    {
        return $this->legalForm;
    }

    public function setLegalForm(string $legalForm): void
    {
        $this->legalForm = $legalForm;
    }

    public function getIncorporationDate(): string
    {
        return $this->incorporationDate;
    }

    public function setIncorporationDate(string $incorporationDate): void
    {
        $this->incorporationDate = $incorporationDate;
    }
}
