<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Fixture;

use Exception;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;
use Tilta\Payment\Api\CustomerAddressBuyerRepositoryInterface;
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use function PHPUnit\Framework\assertInstanceOf;

class BuyerFixture implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID => null,
        CustomerAddressBuyerInterface::BUYER_EXTERNAL_ID => 'buyer-external-id-%uniqid%',
        CustomerAddressBuyerInterface::LEGAL_FORM => 'GMBH',
        CustomerAddressBuyerInterface::INCORPORATED_AT => '2000-01-01',
        CustomerAddressBuyerInterface::FACILITY_VALID_UNTIL => '2999-10-16 10:50:30',
        CustomerAddressBuyerInterface::FACILITY_TOTAL_AMOUNT => 5000,
    ];

    public function __construct(
        private readonly ServiceFactory $serviceFactory,
        private readonly ProcessorInterface $dataProcessor,
        private readonly DataMerger $dataMerger,
        private readonly ProductMetadataInterface $productMetadata,
    ) {
    }

    public function apply(array $data = []): ?DataObject
    {
        $repository = $this->serviceFactory->create(CustomerAddressBuyerRepositoryInterface::class, 'save');
        $repository->execute(
            [
                'customerAddressBuyer' => $this->prepareData($data),
            ]
        );

        $repository = $this->serviceFactory->create(CustomerAddressBuyerRepositoryInterface::class, 'getByCustomerAddressId');

        return $repository->execute(
            [
                'customerAddressId' => (int) $data[CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID],
            ]
        );
    }

    private function prepareData(array $data): array
    {
        if (!isset($data[CustomerAddressBuyerInterface::CUSTOMER_ADDRESS_ID])) {
            throw new Exception('missing customer_id for facility fixture');
        }

        $data = $this->dataMerger->merge(self::DEFAULT_DATA, $data);

        return $this->dataProcessor->process($this, $data);
    }

    /**
     * @param CustomerAddressBuyerInterface $data
     */
    public function revert(DataObject $data): void
    {
        assertInstanceOf(CustomerAddressBuyerInterface::class, $data);

        $service = $this->serviceFactory->create(CustomerAddressBuyerRepositoryInterface::class, 'deleteById');

        $service->execute(
            [
                'customerAddressBuyerId' => $data->getCustomerAddressId(),
            ]
        );
    }
}
