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
        'Magento_Customer/js/model/address-list'
    ],
    function (addressList) {
        'use strict';

        return function (target) {
            return target.extend({
                defaults: {
                    template: 'LaPoste_ColissimoSimplicite/shipping-address/list'
                }
            });
        };
    }
);
