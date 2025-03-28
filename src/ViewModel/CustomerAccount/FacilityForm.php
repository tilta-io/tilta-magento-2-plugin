<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\ViewModel\CustomerAccount;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Address\Renderer\RendererInterface;
use Magento\Customer\Helper\Address;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tilta\Payment\Helper\Context;
use Tilta\Payment\Helper\SalutationHelper;
use Tilta\Payment\Service\LegalFormService;

class FacilityForm implements ArgumentInterface
{
    public function __construct(
        private readonly Context $context,
        private readonly LegalFormService $legalFormService,
        private readonly Address $addressHelper,
        private readonly Mapper $addressMapper,
        private readonly SalutationHelper $salutationHelper,
    ) {
    }

    public function getLegalForms(): array
    {
        return $this->legalFormService->getLegalForms();
    }

    public function getAddress(): AddressInterface
    {
        return $this->context->getCurrentEditAddress();
    }

    public function getToc(): string
    {
        return (string) __(
            'Invoice Payments are offered in partnership with Tilta. I confirm that I have read and accept the privacy policy of <a href="%1" target="_blank">Tilta</a>.',
            'https://static.tilta.io/data-privacy/buyers/tilta-data-protection-information_v2.0_2024-08-15_en_DE.pdf',
        );
    }

    public function formatAddress(): string
    {
        $renderer = $this->addressHelper->getFormatTypeRenderer('html');
        if (!$renderer instanceof RendererInterface) {
            throw new LocalizedException(__('Can not render address'));
        }

        return (string) $renderer->renderArray($this->addressMapper->toFlatArray($this->getAddress()));
    }

    public function getSalutationOptions(): array
    {
        return $this->salutationHelper->getSalutationOptions();
    }
}
