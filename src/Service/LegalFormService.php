<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Tilta\Sdk\Model\Request\Util\GetLegalFormsRequestModel;
use Tilta\Sdk\Service\Request\Util\GetLegalFormsRequest;

class LegalFormService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly RequestServiceFactory $requestServiceFactory,
        private readonly Json $serializer
    ) {
    }

    public function getLegalForms(): array
    {
        $cacheKey = 'tilta-legal-forms-';
        $cachedResult = $this->cache->load($cacheKey);
        $results = null;
        if (is_string($cachedResult)) {
            $results = $this->serializer->unserialize($cachedResult);
        }

        if (!is_array($results)) {
            $responseModel = $this->requestServiceFactory->get(GetLegalFormsRequest::class)
                ->execute(new GetLegalFormsRequestModel());

            $options = [];
            foreach ($responseModel->getItems() as $code => $label) {
                $options[] = [
                    'value' => $code,
                    'label' => __($label),
                ];
            }

            $results = $options;
            $jsonResult = $this->serializer->serialize($results);
            if (is_string($jsonResult)) {
                $this->cache->save($jsonResult, $cacheKey, [], 3600 * 12);
            }
        }

        return $results;
    }

    public function getLegalFormsOnlyCodes(): array
    {
        return array_map(static fn (array $item) => $item['value'], $this->getLegalForms());
    }
}
