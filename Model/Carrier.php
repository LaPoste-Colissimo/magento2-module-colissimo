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
use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;

/**
 * Shipping method.
 *
 * @author Smile (http://www.smile.fr)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Carrier extends AbstractCarrier implements CarrierInterface
{
    /**
     * Identifier code for current shipping method.
     */
    const CODE = 'colissimosimplicite';

    /**
     * Status code used when the user is back from the Colissimo gateway.
     */
    const STATUS_COMPLETE = 'complete';

    /**
     * Status code used when the Colissimo gateway is not available.
     */
    const STATUS_OFFLINE = 'offline';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     * @var StatusFactory
     */
    protected $trackResultFactory;

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
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param State $appState
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param StatusFactory $trackResultFactory
     * @param Session $checkoutSession
     * @param DataHelper $dataHelper
     * @param ConfigHelper $configHelper
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        State $appState,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        StatusFactory $trackResultFactory,
        Session $checkoutSession,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->appState = $appState;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->trackResultFactory = $trackResultFactory;
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if (!$this->checkPriceRange($request)) {
            return false;
        }

        if (!$this->checkWeightLimit($request)) {
            return false;
        }

        /** @var Result $result */
        $result = $this->rateResultFactory->create();

        /** @var Method $method */
        $method = $this->rateMethodFactory->create();
        $method->setData('carrier', $this->_code);
        $method->setData('carrier_title', $this->getConfigData('title'));
        $method->setData('method', $this->_code);

        if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML) {
            // Admin area: always use home delivery
            $shippingMethodPrices = $this->getShippingMethodPrices($request);
            $methodTitle = $this->getConfigData('name_home');
            $methodPrice = $shippingMethodPrices['default_price'];
        } elseif ($this->checkoutSession->getData('colissimosimplicite_status') === self::STATUS_COMPLETE) {
            // Use the data received from the Colissimo gateway
            $deliveryMode = $this->checkoutSession->getData('colissimosimplicite_chosen_delivery_mode');
            $methodTitle = $this->dataHelper->getMethodTitle($deliveryMode);
            $methodPrice = $this->checkoutSession->getData('colissimosimplicite_chosen_shipping_amount');
        } elseif ($this->checkoutSession->getData('colissimosimplicite_status') === self::STATUS_OFFLINE) {
            // Colissimo is not available: use home delivery
            $shippingMethodPrices = $this->getShippingMethodPrices($request);
            $methodTitle = $this->getConfigData('name_home');
            $methodPrice = $shippingMethodPrices['default_price'];
        } else {
            // Display the default title and the lowest price
            $shippingMethodPrices = $this->getShippingMethodPrices($request);
            $methodTitle = $this->getConfigData('name');
            $methodPrice = min($shippingMethodPrices['pickup_price'], $shippingMethodPrices['default_price']);

            // Set the available prices in session
            $this->checkoutSession->setData(
                'colissimosimplicite_available_shipping_amounts',
                [
                    'default' => $shippingMethodPrices['default_price'],
                    'pickup'  => $shippingMethodPrices['pickup_price'],
                ]
            );
        }

        $method->setData('method_title', $methodTitle);
        $method->setPrice($methodPrice);
        $method->setData('cost', $methodPrice);
        $result->append($method);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * {@inheritdoc}
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Get the tracking info (track number, carrier title).
     *
     * @param string $trackNumber
     * @return \Magento\Shipping\Model\Tracking\Result\Status
     */
    public function getTrackingInfo($trackNumber)
    {
        return $this->trackResultFactory->create()
            ->setCarrierTitle($this->getConfigData('title'))
            ->setTracking($trackNumber)
            ->setUrl('https://www.laposte.fr/particulier/outils/suivre-vos-envois?code=' . $trackNumber);
    }

    /**
     * Check if price range is valid.
     *
     * @param RateRequest $request
     * @return bool
     */
    protected function checkPriceRange(RateRequest $request)
    {
        $isPriceRangeValid = true;

        if (is_numeric($this->getConfigData('min_order_total'))
            && $request->getPackageValueWithDiscount() < $this->getConfigData('min_order_total')
        ) {
            $isPriceRangeValid = false;
        }

        if (is_numeric($this->getConfigData('max_order_total'))
            && $request->getPackageValueWithDiscount() > $this->getConfigData('max_order_total')
        ) {
            $isPriceRangeValid = false;
        }

        return $isPriceRangeValid;
    }

    /**
     * Check if weight limit is valid.
     *
     * @param RateRequest $request
     * @return bool
     */
    protected function checkWeightLimit(RateRequest $request)
    {
        $isWeightLimitValid = true;

        if (is_numeric($this->getConfigData('max_weight'))
            && $request->getPackageWeight() > $this->getConfigData('max_weight')
        ) {
            $isWeightLimitValid = false;
        }

        return $isWeightLimitValid;
    }

    /**
     * Check whether to apply free shipping.
     *
     * @param RateRequest $request
     * @return bool
     */
    protected function isFreeShipping(RateRequest $request)
    {
        $minQuotePriceForFreeShipping = $this->getConfigData('min_quote_price_for_free');
        $quotePriceWithDiscount = $request->getPackageValueWithDiscount();

        return $request->getFreeShipping() === true
            || $request->getPackageQty() == $this->getFreeBoxesCount($request)
            || ($minQuotePriceForFreeShipping > 0 && $quotePriceWithDiscount >= $minQuotePriceForFreeShipping);
    }

    /**
     * Get the number of items with free shipping.
     *
     * @param RateRequest $request
     * @return int
     */
    protected function getFreeBoxesCount(RateRequest $request)
    {
        $freeBoxes = 0;
        $items = $request->getAllItems();

        if ($items) {
            foreach ($items as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }

        return $freeBoxes;
    }

    /**
     * Get the number of children items with free shipping.
     *
     * @param mixed $item
     * @return int
     */
    protected function getFreeBoxesCountFromChildren($item)
    {
        $freeBoxes = 0;

        foreach ($item->getChildren() as $child) {
            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }

        return $freeBoxes;
    }

    /**
     * Return available shipping prices.
     *
     * @param RateRequest $request request
     * @return array
     */
    protected function getShippingMethodPrices(RateRequest $request)
    {
        $shippingMethodPrices = [];

        if ($this->isFreeShipping($request)) {
            // Apply free shipping
            $shippingMethodPrices['default_price'] = 0;
            $shippingMethodPrices['pickup_price'] = 0;
        } else {
            // Default delivery costs calculation
            $shippingMethodPrices['default_price'] = $this->getCalculatedPrice(
                $request,
                $this->getConfigData('amount_basetype'),
                $this->getConfigData('amount_calculation')
            );

            // Pickup delivery costs calculation
            $amountCalculationPickup = (string) $this->getConfigData('amount_calculation_pickup');
            if ($amountCalculationPickup !== '') {
                $shippingMethodPrices['pickup_price'] = $this->getCalculatedPrice(
                    $request,
                    $this->getConfigData('amount_basetype_pickup'),
                    $amountCalculationPickup
                );
            } else {
                $shippingMethodPrices['pickup_price'] = $shippingMethodPrices['default_price'];
            }
        }

        return $shippingMethodPrices;
    }

    /**
     * Get the delivery costs depending on calculation method.
     *
     * @param RateRequest $request
     * @param string $amountBaseType
     * @param string $amountCalculation
     * @return float
     * @throws \Exception
     */
    protected function getCalculatedPrice(RateRequest $request, $amountBaseType, $amountCalculation)
    {
        if ($amountBaseType === 'fixed') {
            // Fixed amount
            if (is_numeric($amountCalculation)) {
                $calculatedPrice = $amountCalculation;
            } else {
                $message = 'Colissimo: the configuration value for "Shipping costs calculation" has to be a number formatted like "5.00".';
                throw new \Exception(__($message));
            }
        } else {
            $rules = json_decode($amountCalculation, true);

            if (empty($rules) || !is_array($rules)) {
                $message = 'Colissimo: the configuration value for "Shipping costs calculation" is not well-formed.';
                throw new \Exception(__($message));
            }

            // Sort by descending order
            krsort($rules, SORT_NUMERIC);

            if ($amountBaseType === 'per_weight') {
                // Calculate shipping costs depending on shipping weight range
                $calculatedPrice = $this->getCalculatedPriceByWeightRange($request, $rules);
            } elseif ($amountBaseType === 'per_amount') {
                // Calculate shipping costs depending on shipping price range
                $calculatedPrice = $this->getCalculatedPriceByPriceRange($request, $rules);
            } else {
                $message = 'Colissimo: the configuration value for "Shipping costs calculation" is not available.';
                throw new \Exception(__($message));
            }
        }

        return $calculatedPrice;
    }

    /**
     * Calculate shipping costs depending on shipping price range.
     *
     * @param RateRequest $request
     * @param array $rules
     * @return float
     */
    protected function getCalculatedPriceByPriceRange(RateRequest $request, $rules)
    {
        $totalAmount = $request->getPackageValueWithDiscount();

        $calculatedPrice = false;
        foreach ($rules as $w => $p) {
            $calculatedPrice = $p;
            if ($w <= $totalAmount) {
                break;
            }
        }

        return $calculatedPrice;
    }

    /**
     * Calculate shipping costs depending on shipping weight range.
     *
     * @param RateRequest $request
     * @param array $rules
     * @return float
     */
    protected function getCalculatedPriceByWeightRange(RateRequest $request, $rules)
    {
        // Get order total weight (in kilograms)
        $totalWeight = $request->getPackageWeight();

        // Calculate shipping costs depending on shipping price range
        $calculatedPrice = false;
        foreach ($rules as $w => $p) {
            $calculatedPrice = $p;
            if ($w <= $totalWeight) {
                break;
            }
        }

        return $calculatedPrice;
    }
}
