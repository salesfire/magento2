<?php

namespace Salesfire\Salesfire\Block\Adminhtml\Buttons;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Salesfire GenerateFeed Template for Admin
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.3.0
 */
class GenerateFeed extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\UrlInterface|null
     */
    protected $_urlBuilder = null;
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
    }
    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'sf-generate_button',
                'label' => __('Force Feed Generation'),
            ]
        );

        /** @var \Magento\Backend\Block\Template $block */
        $block = $this->_layout->createBlock('\Salesfire\Salesfire\Block\Adminhtml\Buttons\GenerateFeed');
        $block->setTemplate('Salesfire_Salesfire::generate_feed.phtml')
            ->setChild('button', $button)
            ->setData('select_html', parent::_getElementHtml($element));

        return $block->toHtml();
    }

    public function getAjaxUrl()
    {
        return $this->getUrl('salesfire/feed/generate');
    }
}
