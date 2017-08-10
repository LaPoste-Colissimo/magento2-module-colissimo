<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 *
 * @rewrite to use Colissimoshipping address when it is possible
 *
 * @copyright Copyright (c) 2010 La Poste
 * @author    Smile (http://www.smile.fr)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace LaPoste\ColissimoSimplicite\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote;

/**
 * Plugin for the quote model.
 *
 * @author Smile (http://www.smile.fr)
 */
class QuotePlugin
{
    /**
     * Use the quote shipping address when the selected shipping method is Colissimo.
     *
     * @param Quote $quote
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @return Quote
     */
    public function aroundAssignCustomer(Quote $quote, $proceed, CustomerInterface $customer)
    {
        $shippingAddress = $quote->getShippingAddress();

        if ($shippingAddress->getShippingMethod() === 'colissimosimplicite_colissimosimplicite') {
            // Use the quote shipping address instead of the default customer shipping address
            $result = $quote->assignCustomerWithAddressChange($customer, null, $shippingAddress);
        } else {
            $result = $proceed($customer);
        }

        return $result;
    }
}
