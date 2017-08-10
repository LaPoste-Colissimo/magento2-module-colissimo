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
namespace LaPoste\ColissimoSimplicite\Observer;

use LaPoste\ColissimoSimplicite\Helper\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Update order with Colissimo data.
 *
 * @author Smile (http://www.smile.fr)
 */
class AddColissimoDataToOrder implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @param Session $checkoutSession
     */
    public function __construct(Session $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getShippingMethod() === 'colissimosimplicite_colissimosimplicite') {
            $colissimoData = $this->checkoutSession->getData('colissimosimplicite_shipping_data');

            if (is_array($colissimoData)) {
                $colissimoDataJson = json_encode($colissimoData, JSON_FORCE_OBJECT);
                $order->setData(Config::FIELD_COLISSIMO_DATA, $colissimoDataJson);
            }
        }
    }
}
