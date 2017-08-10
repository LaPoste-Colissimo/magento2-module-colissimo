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
namespace LaPoste\ColissimoSimplicite\Helper;

use LaPoste\ColissimoSimplicite\Model\System\Config\Source\Tax;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Tax\Model\Calculation as TaxCalculation;

/**
 * Data helper.
 *
 * @author Smile (http://www.smile.fr)
 */
class Data extends AbstractHelper
{
    /**
     * Code for home delivery mode.
     */
    const DELIVERY_MODE_HOME = 'DOM';

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var TaxConfig
     */
    protected $taxConfig;

    /**
     * @var TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $configHelper
     * @param TaxConfig $taxConfig
     * @param TaxCalculation $taxCalculation
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $configHelper,
        TaxConfig $taxConfig,
        TaxCalculation $taxCalculation
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
        $this->taxConfig = $taxConfig;
        $this->taxCalculation = $taxCalculation;
    }

    /**
     * Clear current checkout session.
     *
     * @param bool $withShippingAmounts
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function clearCheckoutSession($withShippingAmounts = true)
    {
        if ($this->checkoutSession) {
            $this->checkoutSession->unsetData('colissimosimplicite_status');
            $this->checkoutSession->unsetData('colissimosimplicite_shipping_data');

            // Clear data returned by the Colissimo gateway
            $this->checkoutSession->unsetData('colissimosimplicite_chosen_delivery_mode');
            $this->checkoutSession->unsetData('colissimosimplicite_chosen_shipping_amount');

            // Clear quote subtotal
            $this->checkoutSession->unsetData('colissimosimplicite_quote_subtotal');

            if ($withShippingAmounts) {
                // Clear shipping amounts calculated by the carrier
                $this->checkoutSession->unsetData('colissimosimplicite_available_shipping_amounts');
            }
        }
    }

    /**
     * Get a hash representing the current state of the quote.
     *
     * @param Quote $quote
     * @return string
     */
    public function getQuoteHash(Quote $quote)
    {
        $hashArray = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            /** @var \Magento\Quote\Model\Quote\Item $item */
            $hashArray[] = 'type=' . $item->getProductType() . '&sku=' . $item->getSku() . '&qty=' . $item->getQty();
        }

        sort($hashArray);

        return sha1(implode(',', $hashArray));
    }

    /**
     * Return method title depending on delivery mode.
     *
     * @param string $deliveryMode
     * @return string
     */
    public function getMethodTitle($deliveryMode)
    {
        if ($deliveryMode === self::DELIVERY_MODE_HOME) {
            $methodTitle = $this->configHelper->getHomeDeliveryName();
        } elseif (in_array($deliveryMode, $this->configHelper->getPickupPointCodes())) {
            $methodTitle = $this->configHelper->getPickupDeliveryName();
        } elseif (in_array($deliveryMode, $this->configHelper->getPostOfficeCodes())) {
            $methodTitle = $this->configHelper->getPostOfficeDeliveryName();
        } else {
            $methodTitle = $this->configHelper->getDefaultDeliveryName();
        }

        return $methodTitle;
    }

    /**
     * Get address specific information (street, floor, building, etc.).
     *
     * @param Address $shippingAddress
     * @param int $line
     * @return string
     */
    public function getStreetLine(Address $shippingAddress, $line)
    {
        $arrayStreet = $shippingAddress->getStreet();
        $streetLine = '';
        if (0 < $line && $line <= count($arrayStreet)) {
            $streetLine = $arrayStreet[$line - 1];
        }

        return $streetLine;
    }

    /**
     * Get order total weight formatted as required by Colissimo.
     *
     * @param Address $shippingAddress
     * @return int
     */
    public function getFormattedWeight(Address $shippingAddress)
    {
        $weight = str_replace(',', '.', $shippingAddress->getWeight());
        // Convert Kg to g
        $weight = $weight * 1000;

        return number_format($weight, 0, '.', '');
    }

    /**
     * Get the phone number of current logged in user.
     * Only mobile phone number are allowed.
     * Phone number validation depends on the destination country.
     *
     * @param Address $shippingAddress
     * @return string
     */
    public function getFormattedPhoneNumber(Address $shippingAddress)
    {
        $country = $shippingAddress->getCountryId();
        $phone = $shippingAddress->getTelephone();
        $isValid = true;

        // Remove non numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        switch ($country) {
            case 'FR':
                // Replace +33 by 0
                $phone = str_replace('+33', '0', $phone);

                // Only phone numbers starting by 06 or 07 are available
                $firstTwoNumbers = substr($phone, 0, 2);
                $isValid = $firstTwoNumbers === '06' || $firstTwoNumbers === '07';
                break;

            case 'BE':
                // The phone number is valid if starting by +324
                $firstFourNumbers = substr($phone, 0, 4);
                $isValid = $firstFourNumbers === '+324';
                break;
        }

        return $isValid ? $phone : '';
    }

    /**
     * Get the Magento prefix from the Colissimo prefix.
     *
     * @param string $prefix
     * @return string
     */
    public function getPrefixForMagento($prefix)
    {
        $prefixMagento = '';
        $mapPrefix = $this->configHelper->getPrefixMapping();
        if (in_array($prefix, $mapPrefix)) {
            $prefixMagento = array_search($prefix, $mapPrefix);
        }

        return $prefixMagento;
    }

    /**
     * Get the Colissimo prefix from the Magento prefix.
     *
     * @param string $prefix
     * @return string
     */
    public function getPrefixForColissimo($prefix)
    {
        $colissimoPrefix = '';
        $mapPrefix = $this->configHelper->getPrefixMapping();
        if (isset($mapPrefix[$prefix])) {
            $colissimoPrefix = $mapPrefix[$prefix];
        }

        return $colissimoPrefix;
    }

    /**
     * Convert the shipping amount sent to the Colissimo gateway to excl. or incl. tax
     * depending on the module configuration.
     *
     * @param float $amount
     * @param Address $shippingAddress
     * @return float
     */
    public function getShippingAmountForColissimo($amount, Address $shippingAddress)
    {
        $store = $shippingAddress->getQuote()->getStore();

        if ($this->taxConfig->getShippingTaxClass($store)) {
            if ($this->taxConfig->shippingPriceIncludesTax($store)) {
                if ($this->configHelper->getTaxDisplay() == Tax::EXCLUDING_TAX) {
                    // Remove the tax from the shipping amount before sending it to the Colissimo gateway
                    $taxRate = $this->getShippingTaxRate($shippingAddress);
                    if ($taxRate) {
                        $amount = $this->removeTaxRate($amount, $taxRate);
                    }
                }
            } elseif ($this->configHelper->getTaxDisplay() == Tax::INCLUDING_TAX) {
                // Apply the tax to the shipping amount before sending it to the Colissimo gateway
                $taxRate = $this->getShippingTaxRate($shippingAddress);
                if ($taxRate) {
                    $amount = $this->applyTaxRate($amount, $taxRate);
                }
            }
        }

        return number_format($amount, 2, '.', '');
    }

    /**
     * Convert the shipping amount received from the Colissimo gateway to excl. or incl. tax
     * depending on the module configuration.
     *
     * @param float $amount
     * @param Address $shippingAddress
     * @return float
     */
    public function getShippingAmountForMagento($amount, Address $shippingAddress)
    {
        $store = $shippingAddress->getQuote()->getStore();

        if ($this->taxConfig->getShippingTaxClass($store)) {
            if ($this->taxConfig->shippingPriceIncludesTax($store)) {
                if ($this->configHelper->getTaxDisplay() == Tax::EXCLUDING_TAX) {
                    // Apply the tax to the shipping amount that was sent by the Colissimo gateway
                    $taxRate = $this->getShippingTaxRate($shippingAddress);
                    if ($taxRate) {
                        $amount = $this->applyTaxRate($amount, $taxRate);
                    }
                }
            } elseif ($this->configHelper->getTaxDisplay() == Tax::INCLUDING_TAX) {
                // Remove the tax from the shipping amount that was sent by the Colissimo gateway
                $taxRate = $this->getShippingTaxRate($shippingAddress);
                if ($taxRate) {
                    $amount = $this->removeTaxRate($amount, $taxRate);
                }
            }
        }

        return $amount;
    }

    /**
     * Get the shipping tax rate applied to a shipping address.
     *
     * @param Address $shippingAddress
     * @return float
     */
    protected function getShippingTaxRate(Address $shippingAddress)
    {
        $taxShippingClass = $this->taxConfig->getShippingTaxClass();
        if (!$taxShippingClass) {
            return null;
        }

        $quote = $shippingAddress->getQuote();

        $rateRequest = $this->taxCalculation->getRateRequest(
            $shippingAddress,
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $quote->getStore(),
            $quote->getCustomerId()
        );

        $rateRequest->setProductClassId($taxShippingClass);

        return $this->taxCalculation->getRate($rateRequest);
    }

    /**
     * Apply a tax rate from an amount.
     *
     * @param float $amount
     * @param float $rate
     * @return float
     */
    protected function applyTaxRate($amount, $rate)
    {
        return $amount * (1 + $rate / 100);
    }

    /**
     * Remove a tax rate from an amount.
     *
     * @param float $amount
     * @param float $rate
     * @return float
     */
    protected function removeTaxRate($amount, $rate)
    {
        return $amount / (1 + $rate / 100);
    }
}
