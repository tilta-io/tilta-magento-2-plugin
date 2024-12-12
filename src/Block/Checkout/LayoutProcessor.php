<?php
/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Psr\Log\LoggerInterface;
use Throwable;
use Tilta\Payment\Service\LegalFormService;

class LayoutProcessor implements LayoutProcessorInterface
{
    public function __construct(
        private readonly ArrayManager $arrayManager,
        private readonly LegalFormService $legalFormService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process($jsLayout): array
    {
        $path = $this->arrayManager->findPath('tilta-request-facility-form-fieldset', $jsLayout);
        if (empty($path)) {
            return $jsLayout;
        }

        try {
            $jsLayout = $this->arrayManager->set($path . '/children/legal_form/options', $jsLayout, $this->legalFormService->getLegalForms());
        } catch (Throwable $throwable) {
            $this->logger->error('Tilta: Error fetching legal forms: ' . $throwable->getMessage());
        }

        return $jsLayout;
    }
}
