/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 *
 * @copyright 2017 La Poste
 * @license   Open Software License ("OSL") v. 3.0
 */
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'LaPoste_ColissimoSimplicite/js/view/shipping-mixin': true
            },
            'Magento_Checkout/js/view/payment': {
                'LaPoste_ColissimoSimplicite/js/view/payment-mixin': true
            },
            'Magento_Checkout/js/view/shipping-address/list': {
                'LaPoste_ColissimoSimplicite/js/view/address-list-mixin': true
            },
            'Magento_Checkout/js/model/step-navigator': {
                'LaPoste_ColissimoSimplicite/js/model/step-navigator-mixin': true
            }
        }
    }
};
