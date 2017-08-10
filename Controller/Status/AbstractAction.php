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
namespace LaPoste\ColissimoSimplicite\Controller\Status;

use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use LaPoste\ColissimoSimplicite\Helper\Gateway as GatewayHelper;
use LaPoste\ColissimoSimplicite\Model\Carrier;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Colissimo gateway status controller.
 *
 * @author Smile (http://www.smile.fr)
 */
abstract class AbstractAction extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var GatewayHelper
     */
    protected $gatewayHelper;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param ConfigHelper $configHelper
     * @param GatewayHelper $gatewayHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ConfigHelper $configHelper,
        GatewayHelper $gatewayHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
        $this->gatewayHelper = $gatewayHelper;
    }

    /**
     * Check whether the Colissimo gateway is available.
     *
     * @return bool
     */
    abstract public function isGatewayAvailable();

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->isGatewayAvailable()) {
            $responseData = ['isOffline' => false, 'methodTitle' => ''];
            $this->checkoutSession->unsetData('colissimosimplicite_status');
        } else {
            $responseData = ['isOffline' => true, 'methodTitle' => $this->configHelper->getHomeDeliveryName()];
            $this->checkoutSession->setData('colissimosimplicite_status', Carrier::STATUS_OFFLINE);
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseData);

        return $resultJson;
    }
}
