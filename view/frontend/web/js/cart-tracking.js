define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'underscore'
], function ($, customerData, _) {
    'use strict';

    /**
     * A variable to hold a deep copy of the cart data from the previous state.
     * This is used to compare against the new state to detect changes.
     */
    var prevCartData = {};

    /**
     * Compares the new cart state with the old to find any added products or increased quantities.
     * It uses the product SKU as a stable identifier for comparison.
     * @param {Object} newCart - The new cart data object.
     * @param {Object} oldCart - The previous cart data object.
     * @returns {Object|null} The added product item or null if none is found.
     */
    function findAddedProduct(newCart, oldCart) {
        var oldItems = oldCart.items || [];
        var newItems = newCart.items || [];
        var oldItemsBySku = _.indexBy(oldItems, 'product_sku');
        var newItemsBySku = _.indexBy(newItems, 'product_sku');

        // Case 1: A completely new SKU has been added to the cart.
        var newItemSku = _.find(_.keys(newItemsBySku), function (sku) {
            return !oldItemsBySku[sku];
        });

        if (newItemSku) {
            return newItemsBySku[newItemSku];
        }

        // Case 2: The quantity of an existing SKU has been increased.
        var updatedItemSku = _.find(_.keys(newItemsBySku), function (sku) {
            return oldItemsBySku[sku] && parseInt(newItemsBySku[sku].qty) > parseInt(oldItemsBySku[sku].qty);
        });

        if (updatedItemSku) {
            var item = $.extend(true, {}, newItemsBySku[updatedItemSku]);
            item.qty_diff = parseInt(item.qty) - parseInt(oldItemsBySku[updatedItemSku].qty);
            return item;
        }

        return null;
    }

    /**
     * Compares the new cart state with the old to find any removed products or decreased quantities.
     * It uses the product SKU as a stable identifier for comparison.
     * @param {Object} newCart - The new cart data object.
     * @param {Object} oldCart - The previous cart data object.
     * @returns {Object|null} The removed product item or null if none is found.
     */
    function findRemovedProduct(newCart, oldCart) {
        var oldItems = oldCart.items || [];
        var newItems = newCart.items || [];
        var oldItemsBySku = _.indexBy(oldItems, 'product_sku');
        var newItemsBySku = _.indexBy(newItems, 'product_sku');

        // Case 1: An SKU has been completely removed from the cart.
        var removedItemSku = _.find(_.keys(oldItemsBySku), function (sku) {
            return !newItemsBySku[sku];
        });

        if (removedItemSku) {
            return oldItemsBySku[removedItemSku];
        }

        // Case 2: The quantity of an existing SKU has been decreased.
        var updatedItemSku = _.find(_.keys(newItemsBySku), function (sku) {
            return oldItemsBySku[sku] && parseInt(newItemsBySku[sku].qty) < parseInt(oldItemsBySku[sku].qty);
        });

        if (updatedItemSku) {
            var item = $.extend(true, {}, oldItemsBySku[updatedItemSku]);
            item.qty_diff = parseInt(oldItemsBySku[updatedItemSku].qty) - parseInt(newItemsBySku[updatedItemSku].qty);
            return item;
        }

        return null;
    }

    /**
     * Subscribes to changes in Magento's cart data.
     * When a change is detected, it compares the new cart state with the previous one
     * and pushes an 'add' or 'remove' event to the sfDataLayer.
     */
    customerData.get('cart').subscribe(function (cartData) {

        // Handle initialising data for the first time.
        if (_.isEmpty(prevCartData)) {
            // If the first data we receive already contains items, it's the first "add to cart" event.
            // We must process it immediately by comparing it against a manually created empty cart.
            if (cartData.items && cartData.items.length > 0) {
                var emptyCart = { items: [] };
                var addedProduct = findAddedProduct(cartData, emptyCart);

                if (addedProduct) {
                    var qty = addedProduct.qty_diff || addedProduct.qty;
                    window.sfDataLayer = window.sfDataLayer || [];
                    window.sfDataLayer.push({
                        'ecommerce': {
                            'add': {
                                'sku': addedProduct.product_sku,
                                'name': addedProduct.product_name,
                                'price': addedProduct.product_price_value,
                                'quantity': qty,
                                'currency': window.sfData.currency || 'GBP',
                                'link': addedProduct.product_url,
                                'image_url': addedProduct.product_image ? addedProduct.product_image.src : ''
                            }
                        }
                    });
                }
            }
            // After processing the first event (or if the initial cart was empty),
            // store the state for the next comparison and stop here for this run.
            prevCartData = $.extend(true, {}, cartData);
            return;
        }

        var addedProduct = findAddedProduct(cartData, prevCartData);

        if (addedProduct) {
            var qty = addedProduct.qty_diff || addedProduct.qty;

            window.sfDataLayer = window.sfDataLayer || [];
            window.sfDataLayer.push({
                'ecommerce': {
                    'add': {
                        'sku': addedProduct.product_sku,
                        'name': addedProduct.product_name,
                        'price': addedProduct.product_price_value,
                        'quantity': qty,
                        'currency': window.sfData.currency || 'GBP',
                        'link': addedProduct.product_url,
                        'image_url': addedProduct.product_image ? addedProduct.product_image.src : ''
                    }
                }
            });
        } else {
            var removedProduct = findRemovedProduct(cartData, prevCartData);
            if (removedProduct) {
                var qty = removedProduct.qty_diff || removedProduct.qty;

                window.sfDataLayer = window.sfDataLayer || [];
                window.sfDataLayer.push({
                    'ecommerce': {
                        'remove': {
                            'sku': removedProduct.product_sku,
                            'name': removedProduct.product_name,
                            'price': removedProduct.product_price_value,
                            'quantity': qty,
                            'currency': window.sfData.currency || 'GBP',
                            'link': removedProduct.product_url,
                            'image_url': removedProduct.product_image ? removedProduct.product_image.src : ''
                        }
                    }
                });
            }
        }

        // After processing, store a deep copy of the new cart state for the next comparison.
        prevCartData = $.extend(true, {}, cartData);
    });
});
