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
    let prevCartData = {};

    /**
     * Compares the new cart state with the old to find any added products or increased quantities.
     * It uses the product SKU as a stable identifier for comparison.
     * @param {Object} newCart - The new cart data object.
     * @param {Object} oldCart - The previous cart data object.
     * @returns {Object|null} The added product item or null if none is found.
     */
    function findAddedProduct(newCart, oldCart) {
        const oldItems = oldCart.items || [];
        const newItems = newCart.items || [];
        const oldItemsBySku = _.indexBy(oldItems, 'product_sku');
        const newItemsBySku = _.indexBy(newItems, 'product_sku');

        // Case 1: A completely new SKU has been added to the cart.
        const newItemSku = _.find(_.keys(newItemsBySku), function (sku) {
            return !oldItemsBySku[sku];
        });

        if (newItemSku) {
            return newItemsBySku[newItemSku];
        }

        // Case 2: The quantity of an existing SKU has been increased.
        const updatedItemSku = _.find(_.keys(newItemsBySku), function (sku) {
            return oldItemsBySku[sku] && parseInt(newItemsBySku[sku].qty) > parseInt(oldItemsBySku[sku].qty);
        });

        if (updatedItemSku) {
            const item = $.extend(true, {}, newItemsBySku[updatedItemSku]);
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
        const oldItems = oldCart.items || [];
        const newItems = newCart.items || [];
        const oldItemsBySku = _.indexBy(oldItems, 'product_sku');
        const newItemsBySku = _.indexBy(newItems, 'product_sku');

        // Case 1: An SKU has been completely removed from the cart.
        const removedItemSku = _.find(_.keys(oldItemsBySku), function (sku) {
            return !newItemsBySku[sku];
        });

        if (removedItemSku) {
            return oldItemsBySku[removedItemSku];
        }

        // Case 2: The quantity of an existing SKU has been decreased.
        const updatedItemSku = _.find(_.keys(newItemsBySku), function (sku) {
            return oldItemsBySku[sku] && parseInt(newItemsBySku[sku].qty) < parseInt(oldItemsBySku[sku].qty);
        });

        if (updatedItemSku) {
            const item = $.extend(true, {}, oldItemsBySku[updatedItemSku]);
            item.qty_diff = parseInt(oldItemsBySku[updatedItemSku].qty) - parseInt(newItemsBySku[updatedItemSku].qty);
            return item;
        }

        return null;
    }

    /**
     * Builds the data layer object and pushes it to the window.sfDataLayer array.
     * @param {string} eventType - The type of event, either 'add' or 'remove'.
     * @param {Object} product - The product data object.
     */
    function pushToDataLayer(eventType, product) {
        const qty = product.qty_diff || product.qty;
        const eventData = {
            'ecommerce': {}
        };

        eventData.ecommerce[eventType] = {
            'sku': product.product_id,
            'name': product.product_name,
            'price': product.product_price_value,
            'quantity': qty,
            'currency': window.sfData.currency || 'GBP',
            'link': product.product_url,
            'image_url': product.product_image && product.product_image.src
                ? product.product_image.src.replace(
                    /\/media\/catalog\/product\/cache\/[^/]+\/+/,
                    '/media/catalog/product/'
                )
                : ''
        };

        window.sfDataLayer = window.sfDataLayer || [];
        window.sfDataLayer.push(eventData);
    }

    /**
     * Subscribes to changes in Magento's cart data.
     * When a change is detected, it compares the new cart state with the previous one
     * and pushes an 'add' or 'remove' event to the sfDataLayer.
     */
    customerData.get('cart').subscribe(function (cartData) {

        // On the first run, we need to handle the initialisation.
        if (_.isEmpty(prevCartData)) {

            const firstEventFired = sessionStorage.getItem('sf_first_cart_event_fired');

            if (cartData.items && cartData.items.length > 0) {

                // Only process this as the first event if our session flag has NOT been set.
                if (!firstEventFired) {
                    const emptyCart = { items: [] };
                    const addedProduct = findAddedProduct(cartData, emptyCart);

                    if (addedProduct) {
                        pushToDataLayer('add', addedProduct);
                        // Set the flag in sessionStorage to prevent this from firing again on subsequent page loads.
                        sessionStorage.setItem('sf_first_cart_event_fired', 'true');
                    }
                }
            }

            prevCartData = $.extend(true, {}, cartData);
            return;
        }

        const addedProduct = findAddedProduct(cartData, prevCartData);

        if (addedProduct) {
            pushToDataLayer('add', addedProduct);
        } else {
            const removedProduct = findRemovedProduct(cartData, prevCartData);
            if (removedProduct) {
                pushToDataLayer('remove', removedProduct);
            }
        }

        // After processing, store a deep copy of the new cart state for the next comparison.
        prevCartData = $.extend(true, {}, cartData);

        // If the cart is now empty, reset the session flag so the next "first add" can be tracked.
        if (cartData.items && cartData.items.length === 0) {
            sessionStorage.removeItem('sf_first_cart_event_fired');
        }
    });
});
