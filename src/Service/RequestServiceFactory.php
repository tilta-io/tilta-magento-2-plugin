<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Service;

use Magento\Framework\ObjectManagerInterface;
use Tilta\Sdk\HttpClient\TiltaClient;
use Tilta\Sdk\Service\Request\AbstractRequest;
use Tilta\Sdk\Util\TiltaClientFactory;

final class RequestServiceFactory
{
    private TiltaClient $client;

    public function __construct(
        private readonly ConfigService $config,
        private readonly ObjectManagerInterface $objectManager
    ) {
    }

    /**
     * @template T of AbstractRequest
     * @param class-string<T> $classString
     * @return T
     */
    public function get(string $classString)
    {
        /** @var T $instance */
        $instance = $this->objectManager->get($classString);
        $instance->setClient($this->getClient());

        return $instance;
    }

    private function getClient(): TiltaClient
    {
        return $this->client ??= TiltaClientFactory::getClientInstance(
            $this->config->getToken(),
            $this->config->isSandboxEnabled()
        );
    }
}
