<?php
declare(strict_types=1);

namespace Salesfire\Salesfire\ViewModel;

use Magento\Catalog\Helper\Data\Proxy as TaxHelper;
use Magento\Checkout\Model\Session as CheckoutSession;;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Salesfire\Formatter as SalesfireFormatter;
use Salesfire\Salesfire\Block\Script as SalesfireScript;
use Salesfire\Salesfire\Helper\Data as SalesfireHelper;
use Salesfire\Types\Product as SalesfireProductType;
use Salesfire\Types\Transaction as SalesfireTransactionType;

class DataLayer implements ArgumentInterface
{
    /** @var TaxHelper */
    private $taxHelper;

    /** @var CheckoutSession $checkoutSession */
    private $checkoutSession;

    /** @var RequestInterface */
    private $request;

    /** @var Registry */
    private $registry;

    /** @var SalesfireHelper $helper */
    private $helper;

    public function __construct(
        TaxHelper $taxHelper,
        CheckoutSession $checkoutSession,
        RequestInterface $request,
        Registry $registry,
        SalesfireHelper $helper
    ) {
        $this->taxHelper = $taxHelper;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->registry = $registry;
        $this->helper = $helper;
    }

    public function getSiteId()
    {
        return $this->helper->getSiteId();
    }

    public function getData()
    {
        if (!($siteId = $this->getSiteId())) {
            return json_encode([]);
        }

        /**
         * The logic is functionally-equivalent to the _toHtml() function of the custom Salesfire block class herein.
         * Additional comments have been added to highlight specifically where customisations have been made.
         * @see SalesfireScript::_toHtml()
         */

        $formatter = new SalesfireFormatter($siteId);
        $formatter->addPlatform('magento2');

        if ($this->request->getFullActionName() == 'checkout_onepage_success'
            && $order = $this->checkoutSession->getLastRealOrder() // customisation: use checkout session directly rather than class-level helper function
        ) {
            $transaction = new SalesfireTransactionType([
                'id'       => $order->getIncrementId(),
                'shipping' => round($order->getShippingAmount(), 2),
                'currency' => $order->getOrderCurrencyCode(),
                'coupon'   => $order->getCouponCode(),
            ]);

            foreach ($order->getAllVisibleItems() as $product) {
                $variant = '';
                $options = $product->getProductOptions();
                $parent_product_id = $product_id = $product->getProductId();

                if ($product->getHasChildren()) {
                    foreach ($product->getChildrenItems() as $child) {
                        $product_id = $child->getProductId();
                    }
                }

                if (!empty($options) && !empty($options['attribute_info'])) {
                    $variant = implode(', ', array_map(function ($item) {
                        return $item['label'].': '.$item['value'];
                    }, $options['attribute_info']));
                }

                $quantity = $product->getQtyOrdered() ?? 1;
                $pricing = $this->calculateItemPriceAndTax($product, $quantity);

                $transaction->addProduct(new SalesfireProductType([
                    'sku'        => $product_id,
                    'parent_sku' => $parent_product_id,
                    'name'       => $product->getName(),
                    'price'      => round($pricing['price'], 2),
                    'tax'        => round($pricing['tax'], 2),
                    'quantity'   => round($quantity, 2),
                    'variant'    => $variant,
                ]));
            }

            $formatter->addTransaction($transaction);
        }

        if ($product = $this->registry->registry('product')) { // customisation: use registry directly rather than class-level helper function
            // Calculate product tax
            $price = round($this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), false), 2);
            $tax = round($this->taxHelper->getTaxPrice($product, $product->getFinalPrice(), true), 2) - $price;

            $formatter->addProductView(new SalesfireProductType([
                'sku'        => $product->getId(),
                'parent_sku' => $product->getId(),
                'name'       => $product->getName(),
                'price'      => $price,
                'tax'        => $tax,
            ]));
        }

        // customisation: return only the JSON as the template is responsible for pushing the data to the sfDataLayer
        return $formatter->toJson();
    }

    /**
     * This function has been copied as-is from the custom Salesfire block class, save for the function visibility which
     * has been set to public rather than private for improved extensibility.
     */
    public function calculateItemPriceAndTax($product, $quantity)
    {
        // Row totals represent all items in the order line (e.g., if qty=4, this is the total for all 4)
        $rowTotal = $product->getRowTotal() ?: 0;
        $rowTax = $product->getTaxAmount() ?: 0;

        if ($quantity <= 0) {
            return ['price' => 0, 'tax' => 0];
        }

        return [
            'price' => $rowTotal / $quantity,
            'tax' => $rowTax / $quantity,
        ];
    }
}
