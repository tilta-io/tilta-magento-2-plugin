<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Tests\Unit\Gatway\Command;

use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Tilta\Payment\Gateway\Command\TiltaCommand;
use Tilta\Payment\Gateway\ResponseHandler\HandlerInterface;
use Tilta\Payment\Service\RequestServiceFactory;
use Tilta\Sdk\Exception\GatewayException\UnexpectedServerResponse;
use Tilta\Sdk\Model\Order;
use Tilta\Sdk\Model\Request\Order\GetOrderDetailsRequestModel;
use Tilta\Sdk\Service\Request\Order\GetOrderDetailsRequest;

class TiltaCommandTest extends TestCase
{
    private BuilderInterface $requestBuilder;

    private RequestServiceFactory $requestServiceFactory;

    private HandlerInterface $responseHandler;

    protected function setUp(): void
    {
        $this->requestBuilder = $this->createMock(BuilderInterface::class);
        $this->requestServiceFactory = $this->createMock(RequestServiceFactory::class);
        $this->responseHandler = $this->createMock(HandlerInterface::class);
    }

    public function testExecute(): void
    {
        $response = new Order();

        $command = new TiltaCommand($this->requestBuilder, $this->requestServiceFactory, '', $this->createMock(ObjectManagerInterface::class));

        $this->requestBuilder->method('build')->willReturn(['request_model' => new GetOrderDetailsRequestModel('test')]);
        $requestService = $this->createMock(GetOrderDetailsRequest::class);

        $requestService->expects($this->once())->method('execute')->willReturn($response);
        $this->requestServiceFactory->method('get')->willReturn($requestService);
        $command->execute([]);
    }

    public function testExecuteWithResponseHandler(): void
    {
        $response = new Order();

        $command = new TiltaCommand($this->requestBuilder, $this->requestServiceFactory, '', $this->createMock(ObjectManagerInterface::class), $this->responseHandler);

        $this->requestBuilder->method('build')->willReturn(['request_model' => new GetOrderDetailsRequestModel('test')]);
        $requestService = $this->createMock(GetOrderDetailsRequest::class);
        $requestService->expects($this->once())->method('execute')->willReturn($response);
        $this->requestServiceFactory->method('get')->willReturn($requestService);
        $this->responseHandler->expects($this->once())->method('handle')->with([], $response);

        $command->execute([]);
    }

    public function testMissingRequestModel(): void
    {
        $command = new TiltaCommand($this->requestBuilder, $this->requestServiceFactory, '', $this->createMock(ObjectManagerInterface::class));

        $this->expectException(RuntimeException::class);
        $command->execute(['tst' => 'te']);
    }

    public function testInvalidRequestModel(): void
    {
        $command = new TiltaCommand($this->requestBuilder, $this->requestServiceFactory, '', $this->createMock(ObjectManagerInterface::class));

        $this->expectException(RuntimeException::class);
        $command->execute(['request_model' => new stdClass()]);
    }

    public function testExceptionGotHandled(): void
    {
        $command = new TiltaCommand($this->requestBuilder, $this->requestServiceFactory, '', $this->createMock(ObjectManagerInterface::class));

        $this->requestBuilder->method('build')->willReturn(['request_model' => new GetOrderDetailsRequestModel('test')]);
        $requestService = $this->createMock(GetOrderDetailsRequest::class);
        $requestService->method('execute')->willThrowException(new UnexpectedServerResponse(300));
        $this->requestServiceFactory->method('get')->willReturn($requestService);

        $this->expectException(CommandException::class);
        $command->execute([]);
    }
}
