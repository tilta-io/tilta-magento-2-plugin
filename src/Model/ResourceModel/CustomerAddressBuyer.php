<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CustomerAddressBuyer extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('tilta_buyer_data', 'entity_id');
    }
}
