<?php
namespace Salesfire\Salesfire\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\ObjectManagerInterface;

/**
 * Salesfire Script Block
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version.   1.2.1
 */
class Script extends Template
{
    public $helperData;
    public $product = null;
    public $order   = null;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    public $request;
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    public $taxHelper;

    public function __construct(
        Context $context,
        \Salesfire\Salesfire\Helper\Data $helperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Data $taxHelper,
        array $data = []
    ) {
        $this->helperData      = $helperData;
        $this->checkoutSession = $checkoutSession;
        $this->request         = $request;
        $this->registry        = $registry;
        $this->taxHelper       = $taxHelper;
        parent::__construct($context, $data);
    }

    public function getHelper()
    {
        return $this->helperData;
    }

    public function getOrder()
    {
        if ($this->order === null) {
            $order = $this->checkoutSession->getLastRealOrder();

            if (! $order->getIncrementId()) {
                return null;
            }

            $this->order = $order;
        }

        return $this->order;
    }

    public function getProduct()
    {
        if ($this->product === null) {
            $this->product = $this->registry->registry('product');

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
        if ($this->request->getFullActionName() == 'checkout_onepage_success' && $order = $this->getOrder()) {
            $transaction = new \Salesfire\Types\Transaction([
                'id'       => $order->getIncrementId(),
                'shipping' => round($order->getShippingAmount(), 2),
                'currency' => $order->getOrderCurrencyCode(),
                'coupon'   => $order->getCouponCode(),
            ]);

            foreach ($order->getAllVisibleItems() as $product) {
                $variant = '';
                $options = $product->getProductOptions();
                if (!empty($options) && !empty($options['attribute_info'])) {
                    $variant = implode(', ', array_map(function ($item) {
                        return $item['label'].': '.$item['value'];
                    }, $options['attribute_info']));
                }

                $transaction->addProduct(new \Salesfire\Types\Product([
                    'sku'        => $product->getProductId(),
                    'parent_sku' => $product->getProductId(),
                    'name'       => $product->getName(),
                    'price'      => round($product->getPrice(), 2),
                    'tax'        => round($product->getTaxAmount(), 2),
                    'quantity'   => round($product->getQtyOrdered()),
                    'variant'    => $variant,
                ]));
            }

            $formatter->addTransaction($transaction);
        }

        // Display product view
        if ($product = $this->getProduct()) {
            // Calculate product tax
            $price = round($this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), false), 2);
            $tax = round($this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), true), 2) - $price;

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
