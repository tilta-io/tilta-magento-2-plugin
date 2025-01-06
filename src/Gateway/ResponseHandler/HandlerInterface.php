<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\ResponseHandler;

use Tilta\Sdk\Model\AbstractModel;

interface HandlerInterface
{
    public function handle(array $handlingSubject, AbstractModel|bool $response): void;
}
