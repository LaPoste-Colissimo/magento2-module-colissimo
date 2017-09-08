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

use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Gateway helper.
 *
 * @author Smile (http://www.smile.fr)
 */
class Gateway extends AbstractHelper
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var bool
     */
    protected $webGatewayAvailable;

    /**
     * @var bool
     */
    protected $mobileGatewayAvailable;

    /**
     * @param Context $context
     * @param CacheInterface $cache
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Context $context,
        CacheInterface $cache,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->cache = $cache;
        $this->configHelper = $configHelper;
    }

    /**
     * Check whether the web gateway is available.
     *
     * @return bool
     */
    public function isWebGatewayAvailable()
    {
        if ($this->webGatewayAvailable === null) {
            $statusUrl = $this->configHelper->getWebGatewayStatusUrl();

            $cacheKey = 'colissimosimplicite_web_gateway_status';
            $available = $this->cache->load($cacheKey);

            if ($available !== false) {
                $this->webGatewayAvailable = (bool) $available;
            } else {
                $this->webGatewayAvailable = $this->isGatewayAvailable($statusUrl);
                $this->cache->save((int) $this->webGatewayAvailable, $cacheKey, [], 15);
            }
        }

        return $this->webGatewayAvailable;
    }

    /**
     * Check whether the mobile gateway is available.
     *
     * @return bool
     */
    public function isMobileGatewayAvailable()
    {
        if ($this->mobileGatewayAvailable === null) {
            $statusUrl = $this->configHelper->getMobileGatewayStatusUrl();

            $cacheKey = 'colissimosimplicite_mobile_gateway_status';
            $available = $this->cache->load($cacheKey);

            if ($available !== false) {
                $this->mobileGatewayAvailable = (bool) $available;
            } else {
                $this->mobileGatewayAvailable = $this->isGatewayAvailable($statusUrl);
                $this->cache->save((int) $this->mobileGatewayAvailable, $cacheKey, [], 15);
            }
        }

        return $this->mobileGatewayAvailable;
    }

    /**
     * Get the Colissimo gateway availability.
     *
     * @param string $statusUrl
     * @return bool
     */
    public function isGatewayAvailable($statusUrl)
    {
        if (!$this->configHelper->isGatewayStatusEnabled()) {
            return true;
        }

        // Send a request to the gateway status URL
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $statusUrl);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HEADER, false);

        try {
            $output = curl_exec($curlHandle);
            $available = strpos($output, '[OK]') !== false;
        } catch (\Exception $e) {
            $this->_logger->alert($e->getMessage());
            $available = false;
        }
        curl_close($curlHandle);

        return $available;
    }
}
