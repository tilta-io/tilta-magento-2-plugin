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
use Tilta\Payment\Service\LegalFormService;

class FacilityForm implements ArgumentInterface
{
    public function __construct(
        private readonly Context $context,
        private readonly LegalFormService $legalFormService,
        private readonly Address $addressHelper,
        private readonly Mapper $addressMapper
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
            'Invoice Payments are offered in partnership with Varengold Bank. I confirm that I have read and accept the privacy policy of <a href="%1" target="_blank">Varengold</a> and <a href="%2" target="_blank">Tilta</a>.',
            'https://static.tilta.io/data-privacy/buyers/varengold-data-protection-information_v2.7_2021-05-01_de_DE.pdf',
            'https://static.tilta.io/data-privacy/buyers/tilta-data-protection-information_v1.0_2023-02-17_de_DE.pdf'
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
}
