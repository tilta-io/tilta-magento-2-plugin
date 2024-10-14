<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Controller\Facility;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Controller\AccountInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;

abstract class AbstractFacility implements AccountInterface
{
    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly Session $customerSession,
        protected readonly RequestInterface $request
    ) {
    }

    protected function getAddress(): AddressInterface
    {
        $addressId = $this->request->getParam('id');

        try {
            $address = $this->addressRepository->getById($addressId);
        } catch (NoSuchEntityException) {
            throw new NotFoundException(__('Requested address not found.'));
        }

        if ((int) $this->customerSession->getCustomerId() !== (int) $address->getCustomerId()) {
            throw new NotFoundException(__('Requested address not found.'));
        }

        return $address;
    }
}
