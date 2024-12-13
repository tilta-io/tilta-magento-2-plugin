<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Gateway\Command;

use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use RuntimeException;
use Tilta\Payment\Gateway\ResponseHandler\HandlerInterface;
use Tilta\Payment\Service\RequestServiceFactory;
use Tilta\Sdk\Exception\TiltaException;
use Tilta\Sdk\Model\AbstractModel;
use Tilta\Sdk\Model\Request\RequestModelInterface;

class TiltaCommand implements CommandInterface
{
    public function __construct(
        private readonly BuilderInterface $requestBuilder,
        private readonly RequestServiceFactory $requestServiceFactory,
        private readonly string $requestServiceClass,
        private readonly ObjectManagerInterface $objectManager,
        private readonly ?HandlerInterface $responseHandler = null,
    ) {
    }

    public function execute(array $commandSubject): ?ResultInterface
    {
        $data = $this->requestBuilder->build($commandSubject);
        if (!is_array($data) || !isset($data['request_model']) || !$data['request_model'] instanceof RequestModelInterface) {
            throw new RuntimeException(sprintf('return value of request-builder %s is not an array or does not contain key `request_model` which has to be a instance of %s', $this->requestBuilder::class, AbstractModel::class));
        }

        $requestService = $this->requestServiceFactory->get('\\' . ltrim($this->requestServiceClass, '\\')); // @phpstan-ignore-line
        try {
            $response = $requestService->execute($data['request_model']);
            $this->responseHandler?->handle($commandSubject, $response);
        } catch (TiltaException $tiltaException) {
            throw new CommandException(__($tiltaException->getMessage()), $tiltaException, $tiltaException->getCode());
        }

        return $this->objectManager->create(ResultInterface::class, [
            'isValid' => true,
        ]);
    }
}
