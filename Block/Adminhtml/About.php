<?php

namespace Salesfire\Salesfire\Block\Adminhtml;

/**
 * Salesfire About Template for Admin
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.1
 */
class About extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'salesfire_about.phtml';

    /**
     * Render fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $columns = $this->getRequest()->getParam('website') || $this->getRequest()->getParam('store') ? 5 : 4;
        return $this->_decorateRowHtml($element, "<td colspan='{$columns}' class='".($this->getRequest()->getParam('store') ? 'salesfire_about_store_config': '')."'>" . $this->toHtml() . '</td>');
    }
}
