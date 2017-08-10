<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module
 * to newer versions in the future.
 *
 * @copyright 2017 La Poste
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace LaPoste\ColissimoSimplicite\Model;

use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use LaPoste\ColissimoSimplicite\Helper\Gateway as GatewayHelper;
use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;

/**
 * JS config provider.
 * Rewrite to add Colissimoshipping address to quote data.
 *
 * @author Smile (http://www.smile.fr)
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var GatewayStatus
     */
    protected $gatewayHelper;

    /**
     * @param Session $checkoutSession
     * @param DataHelper $dataHelper
     * @param ConfigHelper $configHelper
     * @param GatewayHelper $gatewayHelper
     */
    public function __construct(
        Session $checkoutSession,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        GatewayHelper $gatewayHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->gatewayHelper = $gatewayHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $output = [];

        $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
        if ($shippingAddress->getShippingMethod() !== 'colissimosimplicite_colissimosimplicite') {
            return $output;
        }

        // Width breakpoint for mobiles devices
        $output['colissimoWidthBreakpoint'] = $this->configHelper->getWidthBreakpoint();

        if ($this->checkoutSession->getData('colissimosimplicite_status') === Carrier::STATUS_COMPLETE) {
            // Address selected on the Colissimo gateway
            $output['colissimoSimpliciteAddress'] = [
                'customer_id' => $shippingAddress->getCustomerId(),
                'customer_address_id' => $shippingAddress->getCustomerAddressId(),
                'company' => $shippingAddress->getCompany(),
                'telephone' => $shippingAddress->getTelephone(),
                'firstname' => $shippingAddress->getFirstname(),
                'lastname' => $shippingAddress->getLastname(),
                'street' => $shippingAddress->getStreet(),
                'city' => $shippingAddress->getCity(),
                'country_id' => $shippingAddress->getCountryId(),
                'postcode' => $shippingAddress->getPostcode(),
                'region' => $shippingAddress->getRegion(),
                'region_id' => $shippingAddress->getRegionId(),
                'save_in_address_book' => 0,
                'same_as_billing' => (bool) $shippingAddress->getSameAsBilling(),
            ];
        }

        return $output;
    }
}
