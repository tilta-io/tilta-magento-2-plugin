<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Exception;

use Magento\Framework\Exception\LocalizedException;

class MissingBuyerInformationException extends LocalizedException
{
    public function __construct(
        private readonly array $errorMessages
    ) {
        parent::__construct(__('Creating buyer is failed, cause of missing fields.'));
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
