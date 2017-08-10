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

use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Update shipping address with Colissimo data.
 *
 * @author Smile (http://www.smile.fr)
 */
class ClearCheckoutSession implements ObserverInterface
{
    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @param DataHelper $dataHelper
     */
    public function __construct(DataHelper $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $this->dataHelper->clearCheckoutSession();
    }
}
