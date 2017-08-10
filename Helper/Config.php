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

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Config helper.
 *
 * @author Smile (http://www.smile.fr)
 */
class Config extends AbstractHelper
{
    /**
     * Config paths.
     */
    const PATH_CARRIER_IS_ACTIVE         = 'carriers/colissimosimplicite/active';
    const PATH_ACCOUNT_ID                = 'carriers/colissimosimplicite/account';
    const PATH_ENCRYPTION_KEY_PATH       = 'carriers/colissimosimplicite/encryption_key';
    const PATH_GATEWAY_URL_WEB           = 'carriers/colissimosimplicite/gateway_url_web';
    const PATH_GATEWAY_URL_MOBILE        = 'carriers/colissimosimplicite/gateway_url_mobile';
    const PATH_REDIRECT_MESSAGE          = 'carriers/colissimosimplicite/redirect_message';
    const PATH_REDIRECT_URL_ERROR        = 'carriers/colissimosimplicite/redirect_url_on_error';
    const PATH_GATEWAY_STATUS_ENABLED    = 'carriers/colissimosimplicite/gateway_status_enabled';
    const PATH_GATEWAY_STATUS_URL_WEB    = 'carriers/colissimosimplicite/gateway_status_url_web';
    const PATH_GATEWAY_STATUS_URL_MOBILE = 'carriers/colissimosimplicite/gateway_status_url_mobile';
    const PATH_DEFAULT_DELIVERY_NAME     = 'carriers/colissimosimplicite/name';
    const PATH_HOME_DELIVERY_NAME        = 'carriers/colissimosimplicite/name_home';
    const PATH_PICKUP_DELIVERY_NAME      = 'carriers/colissimosimplicite/name_pickup';
    const PATH_POSTOFFICE_DELIVERY_NAME  = 'carriers/colissimosimplicite/name_post_office';
    const PATH_PICKUP_CODES              = 'carriers/colissimosimplicite/pickup_codes';
    const PATH_POST_OFFICE_CODES         = 'carriers/colissimosimplicite/post_office_codes';
    const PATH_TAX_DISPLAY               = 'carriers/colissimosimplicite/tax_display';
    const PATH_PREFIX_MAPPING            = 'carriers/colissimosimplicite/prefix_mapping';
    const WIDTH_BREAKPOINT               = 'carriers/colissimosimplicite/width_breakpoint';
    /**#@-*/

    /**
     * Field added to the order table to store Colissimo data.
     */
    const FIELD_COLISSIMO_DATA = 'colissimosimplicite_data';

    /**
     * Success form URL path.
     */
    const SUCCESS_URL_PATH = 'colissimosimplicite/result/success';

    /**
     * Failure form URL path.
     */
    const FAILURE_URL_PATH = 'colissimosimplicite/result/failure';

    /**
     * Version number sent to the Colissimo gateway.
     */
    const NUM_VERSION = '4.0';

    /**
     * Encoding type.
     */
    const ENCODING_TYPE = 'UTF-8';

    /**
     * @var Store
     */
    protected $store;

    /**
     * @param Context $context
     * @param Store $store
     */
    public function __construct(
        Context $context,
        Store $store
    ) {
        parent::__construct($context);
        $this->store = $store;
    }

    /**
     * Check if the carrier is enabled.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->scopeConfig->getValue(self::PATH_CARRIER_IS_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the account ID.
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->scopeConfig->getValue(self::PATH_ACCOUNT_ID, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the encryption key used to create the signature.
     *
     * @return string
     */
    public function getEncryptionKey()
    {
        return $this->scopeConfig->getValue(self::PATH_ENCRYPTION_KEY_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the URL of the Colissimo web gateway.
     *
     * @return string
     */
    public function getWebGatewayUrl()
    {
        return $this->scopeConfig->getValue(self::PATH_GATEWAY_URL_WEB, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the URL of the Colissimo mobile gateway.
     *
     * @return string
     */
    public function getMobileGatewayUrl()
    {
        return $this->scopeConfig->getValue(self::PATH_GATEWAY_URL_MOBILE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the redirection URL used when an error occurrs (wrong quote totals, missing session data).
     *
     * @return string
     */
    public function getRedirectUrlOnError()
    {
        return $this->_getUrl($this->scopeConfig->getValue(self::PATH_REDIRECT_URL_ERROR, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Get the message displayed during redirection to the FO.
     *
     * @return string
     */
    public function getRedirectMessage()
    {
        return $this->scopeConfig->getValue(self::PATH_REDIRECT_MESSAGE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the whether to check Colissimo gateway availability.
     *
     * @return bool
     */
    public function isGatewayStatusEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::PATH_GATEWAY_STATUS_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get URL to check Colissimo web gateway availability.
     *
     * @return string
     */
    public function getWebGatewayStatusUrl()
    {
        return $this->scopeConfig->getValue(self::PATH_GATEWAY_STATUS_URL_WEB, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get URL to check Colissimo mobile gateway availability.
     *
     * @return string
     */
    public function getMobileGatewayStatusUrl()
    {
        return $this->scopeConfig->getValue(self::PATH_GATEWAY_STATUS_URL_MOBILE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Colissimo default delivery name.
     *
     * @return string
     */
    public function getDefaultDeliveryName()
    {
        return $this->scopeConfig->getValue(self::PATH_DEFAULT_DELIVERY_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Colissimo home delivery name.
     *
     * @return string
     */
    public function getHomeDeliveryName()
    {
        return $this->scopeConfig->getValue(self::PATH_HOME_DELIVERY_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Colissimo pickup delivery name.
     *
     * @return string
     */
    public function getPickupDeliveryName()
    {
        return $this->scopeConfig->getValue(self::PATH_PICKUP_DELIVERY_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Colissimo post office delivery name.
     *
     * @return string
     */
    public function getPostOfficeDeliveryName()
    {
        return $this->scopeConfig->getValue(self::PATH_POSTOFFICE_DELIVERY_NAME, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the tax display mode on the Colissimo gateway (incl. or exc. tax).
     *
     * @return int
     */
    public function getTaxDisplay()
    {
        return $this->scopeConfig->getValue(self::PATH_TAX_DISPLAY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the mapping between Magento and Colissimo prefixes.
     *
     * @return array
     */
    public function getPrefixMapping()
    {
        $map = $this->scopeConfig->getValue(self::PATH_PREFIX_MAPPING, ScopeInterface::SCOPE_STORE);

        return json_decode($map, true);
    }

    /**
     * Get the breakpoint width for mobile devices.
     *
     * @return string
     */
    public function getWidthBreakpoint()
    {
        return $this->scopeConfig->getValue(self::WIDTH_BREAKPOINT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get the success URL path.
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->store->getUrl(self::SUCCESS_URL_PATH);
    }

    /**
     * Get the failure URL path.
     *
     * @return string
     */
    public function getFailureUrl()
    {
        return $this->store->getUrl(self::FAILURE_URL_PATH);
    }

    /**
     * Get the version number.
     *
     * @return string
     */
    public function getNumVersion()
    {
        return self::NUM_VERSION;
    }

    /**
     * Get the encoding type used to transfer the data.
     *
     * @return string
     */
    public function getEncodingType()
    {
        return self::ENCODING_TYPE;
    }

    /**
     * Get all merchant relay points.
     *
     * @return array
     */
    public function getPickupPointCodes()
    {
        $codes = $this->scopeConfig->getValue(self::PATH_PICKUP_CODES, ScopeInterface::SCOPE_STORE);

        return is_array($codes) ? array_keys($codes) : [];
    }

    /**
     * Get all post office codes.
     *
     * @return array
     */
    public function getPostOfficeCodes()
    {
        $codes = $this->scopeConfig->getValue(self::PATH_POST_OFFICE_CODES, ScopeInterface::SCOPE_STORE);

        return is_array($codes) ? array_keys($codes) : [];
    }
}
