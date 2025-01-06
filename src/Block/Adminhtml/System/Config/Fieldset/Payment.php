<?php

/*
 * (c) WEBiDEA
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tilta\Payment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Payment extends Fieldset
{
    /**
     * @var bool
     */
    protected $isCollapsedDefault = true;

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        /**
         * @var SecureHtmlRenderer
         */
        private readonly SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
    }

    protected function _getFrontendClass($element): string
    {
        return parent::_getFrontendClass($element) . ' with-button' . ($this->_isCollapseState($element) ? ' open active' : '');
    }

    protected function _getHeaderTitleHtml($element): string
    {
        $htmlId = $element->getHtmlId();
        $disabled = $this->_isCollapseState($element) ? '' : ' disabled="disabled"';
        $opened = ($this->_isCollapseState($element) ? ' open' : '');
        $configureLabel = __('Configure');
        $closeLabel = __('Close');

        $js = $this->secureRenderer->renderEventListenerAsTag(
            'onclick',
            sprintf("tiltaToggleSolution.call(this, '%s', '%s'); event.preventDefault();", $htmlId, $this->getUrl('adminhtml/*/state')),
            'button#' . $htmlId . '-head'
        );

        return <<<HTML
            <div class="config-heading">
                <div><img src="{$this->getViewFileUrl('Tilta_Payment::images/tilta_logo.svg')}" alt="Tilta" /></div>
                <div class="heading">
                    <strong>{$element->getData('legend')}</strong>
                    <div class="heading-intro">{$element->getData('comment')}</div>
                </div>
                <div class="button-container">
                    <button type="button" {$disabled} class="button action-configure {$opened}" id="{$htmlId}-head">
                        <span class="state-closed">{$configureLabel}</span>
                        <span class="state-opened">{$closeLabel}</span>
                    </button>
                    {$js}
                </div>
            </div>
        HTML;
    }

    protected function _getHeaderCommentHtml($element): string
    {
        return '';
    }

    protected function _getExtraJs($element): string
    {
        $script = "require(['jquery', 'prototype'], function(jQuery){
            window.tiltaToggleSolution = function (id, url) {
                var doScroll = false;
                Fieldset.toggleCollapse(id, url);
                if ($(this).hasClassName(\"open\")) {
                    \$$(\".with-button button.button\").forEach(function(anotherButton) {
                        if (anotherButton != this && $(anotherButton).hasClassName(\"open\")) {
                            $(anotherButton).click();
                            doScroll = true;
                        }
                    }.bind(this));
                }
                if (doScroll) {
                    var pos = Element.cumulativeOffset($(this));
                    window.scrollTo(pos[0], pos[1] - 45);
                }
            }
        });";

        return $this->_jsHelper->getScript($script);
    }

    protected function _isCollapseState($element): bool
    {
        return false;
    }
}
