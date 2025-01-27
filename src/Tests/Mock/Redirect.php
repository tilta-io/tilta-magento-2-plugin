<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Mock;

class Redirect extends \Magento\Framework\Controller\Result\Redirect
{
    public function getUrl()
    {
        return $this->url;
    }
}
