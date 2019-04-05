<?php
namespace Salesfire\Salesfire\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Salesfire\Salesfire\Helper\Data as HelperData;
use Magento\Framework\ObjectManagerInterface;

class Script extends Template
{
    protected $helperData;
    protected $product;
    protected $order;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_taxHelper;

    public function __construct(
        Context $context,
        HelperData $helperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Data $taxHelper,
        array $data = []
    )
    {
        $this->helperData       = $helperData;
        $this->_checkoutSession = $checkoutSession;
        $this->_request         = $request;
        $this->_registry        = $registry;
        $this->_taxHelper       = $taxHelper;
        parent::__construct($context, $data);
    }

    public function getHelper()
    {
        return $this->helperData;
    }

    public function getOrder()
    {
        if (is_null($this->order)) {
            $order = $this->_checkoutSession->getLastRealOrder();

            if (! $order->getIncrementId()) {
                return null;
            }

            $this->order = $order;
        }

        return $this->order;
    }

    public function getProduct()
    {
        if (is_null($this->product)) {
            $this->product = $this->_registry->registry('product');

            if (! $this->product || ! $this->product->getId()) {
                return null;
            }
        }

        return $this->product;
    }

    public function _toHtml()
    {
        if (! $this->getHelper()->isAvailable()) {
            return '';
        }

        $formatter = new \Salesfire\Formatter($this->getHelper()->getSiteId());

        // Display transaction
        if ($this->_request->getFullActionName() == 'checkout_onepage_success' && $order = $this->getOrder()) {
            $transaction = new \Salesfire\Types\Transaction([
                'id'       => $order->getIncrementId(),
                'shipping' => round($order->getShippingAmount(), 2),
                'currency' => $order->getOrderCurrencyCode(),
                'coupon'   => $order->getCouponCode(),
            ]);

            foreach ($order->getAllVisibleItems() as $product) {
                $transaction->addProduct(new \Salesfire\Types\Product([
                    'sku'        => $product->getProductId(),
                    'parent_sku' => $product->getProductId(),
                    'name'       => $product->getName(),
                    'price'      => round($product->getPrice(), 2),
                    'tax'        => round($product->getTaxAmount(), 2),
                    'quantity'   => round($product->getQtyOrdered()),
                    'variant'    => implode(", ", array_map(function($item) {return $item['label'].': '.$item['value'];}, $product->getProductOptions()['attributes_info']))
                ]));
            }

            $formatter->addTransaction($transaction);
        }

        // Display product view
        if ($product = $this->getProduct()) {
            // Calculate product tax
            $price = round($this->_taxHelper->getTaxPrice($product, $product->getFinalPrice(), false), 2);
            $tax = round($this->_taxHelper->getTaxPrice($product, $product->getFinalPrice(), true), 2) - $price;

            $formatter->addProductView(new \Salesfire\Types\Product([
                'sku'        => $product->getId(),
                'parent_sku' => $product->getId(),
                'name'       => $product->getName(),
                'price'      => $price,
                'tax'        => $tax,
            ]));
        }

        return $formatter->toScriptTag();
    }
}
