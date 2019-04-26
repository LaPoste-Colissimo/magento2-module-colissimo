/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 *
 * @copyright 2017 La Poste
 * @license   Open Software License ("OSL") v. 3.0
 */
define(
    [
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/action/select-billing-address'
    ],
    function (
        quote,
        createShippingAddress,
        selectShippingAddress,
        selectBillingAddress
    ) {
        'use strict';

        return function (target) {
            return target.extend({
                navigate: function () {
                    if (window.checkoutConfig.selectedShippingMethod.carrier_code !== 'colissimosimplicite') {
                        return this._super();
                    }

                    if (!window.checkoutConfig.colissimoSimpliciteAddress) {
                        return this._super();
                    }

                    var billingAddress = quote.shippingAddress();
                    var shippingAddress = createShippingAddress(window.checkoutConfig.colissimoSimpliciteAddress);

                    // Prevent the address from being displayed in the address list on the shipping step
                    if (shippingAddress.extension_attributes === undefined) {
                        shippingAddress.extension_attributes = {};
                    }
                    shippingAddress.extension_attributes.isColissimoSimplicite = true;

                    // Use the address returned by Colissimo as the shipping address
                    selectShippingAddress(shippingAddress);

                    // Use the address selected in the previous step (shipping) as the billing address
                    selectBillingAddress(billingAddress);

                    this._super();
                }
            });
        };
    }
);
