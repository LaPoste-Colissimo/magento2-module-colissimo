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
        'Magento_Customer/js/model/address-list'
    ],
    function (
        quote,
        addressList
    ) {
        'use strict';

        return function (target) {
            var oldFunc = target.navigateTo;

            target.navigateTo = function (code, scrollToElementId) {
                if (code === 'shipping' && this.isProcessed(code)) {
                    var shippingMethod = quote.shippingMethod();

                    if (shippingMethod && shippingMethod.carrier_code === 'colissimosimplicite') {
                        if (window.checkoutConfig.colissimoSimpliciteAddress) {
                            // Select the default shipping address when going back to the shipping step
                            var defaultShipping = addressList().find(function (address) {
                                return address.isDefaultShipping();
                            });

                            if (defaultShipping) {
                                quote.shippingAddress(defaultShipping);
                            }

                            delete window.checkoutConfig.colissimoSimpliciteAddress;
                        }
                    }
                }

                oldFunc.apply(target, [code, scrollToElementId]);
            };

            return target;
        };
    }
);
