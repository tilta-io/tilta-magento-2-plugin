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
use Tilta\Payment\Api\Data\CustomerAddressBuyerInterface;
use Tilta\Payment\Helper\SalutationHelper;
use Tilta\Payment\Service\LegalFormService;
use Tilta\Payment\ViewModel\CustomerAccount\FacilityForm;

class LayoutProcessor implements LayoutProcessorInterface
{
    public function __construct(
        private readonly ArrayManager $arrayManager,
        private readonly LegalFormService $legalFormService,
        private readonly SalutationHelper $salutationHelper,
        private readonly LoggerInterface $logger,
        private readonly FacilityForm $facilityForm,
    ) {
    }

    public function process($jsLayout): array
    {
        $path = $this->arrayManager->findPath('tilta-request-facility-form-fieldset', $jsLayout);
        if (empty($path)) {
            return $jsLayout;
        }

        $legalFormOptionPath = $this->arrayManager->findPath(CustomerAddressBuyerInterface::LEGAL_FORM, $jsLayout, $path);
        if (is_string($legalFormOptionPath) && $this->arrayManager->get($legalFormOptionPath, $jsLayout)) {
            try {
                $jsLayout = $this->arrayManager->set($legalFormOptionPath . '/options', $jsLayout, $this->legalFormService->getLegalForms());
            } catch (Throwable $throwable) {
                $this->logger->error('Tilta: Error fetching legal forms: ' . $throwable->getMessage());
            }
        }

        $salutationPath = $this->arrayManager->findPath(CustomerAddressBuyerInterface::SOLE_TRADER_SALUTATION, $jsLayout, $path);
        if (is_string($salutationPath) && $this->arrayManager->get($salutationPath, $jsLayout)) {
            $jsLayout = $this->arrayManager->set($salutationPath . '/options', $jsLayout, $this->salutationHelper->getSalutationOptions());
        }

        $tocPath = $this->arrayManager->findPath('toc', $jsLayout, $path);
        if (is_string($tocPath) && $this->arrayManager->get($tocPath, $jsLayout)) {
            $jsLayout = $this->arrayManager->set($tocPath . '/description', $jsLayout, $this->facilityForm->getToc());
        }

        return $jsLayout;
    }
}
