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

use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use LaPoste\ColissimoSimplicite\Model\Carrier;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observer that validates the session data on the payment page.
 *
 * @author Smile (http://www.smile.fr)
 * @SuppressWarnings(PHPMD.ValidateOnSubmit)
 */
class ValidateOnSubmit implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var bool
     */
    protected $errorOccurred = false;

    /**
     * @var Phrase|null
     */
    protected $errorMessage;

    /**
     * @param Session $checkoutSession
     * @param ManagerInterface $messageManager
     * @param ConfigHelper $configHelper
     * @param DataHelper $dataHelper
     */
    public function __construct(
        Session $checkoutSession,
        ManagerInterface $messageManager,
        ConfigHelper $configHelper,
        DataHelper $dataHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
        if ($shippingAddress->getShippingMethod() !== 'colissimosimplicite_colissimosimplicite') {
            return;
        }

        $status = $this->checkoutSession->getData('colissimosimplicite_status');

        if ($status !== Carrier::STATUS_COMPLETE && $status !== Carrier::STATUS_OFFLINE) {
            $this->throwError(__('An unexpected error occurred. Please try again later.'));
        }

        if ($status === Carrier::STATUS_COMPLETE) {
            $this->validateQuote($observer);
        }
    }

    /**
     * Make sure the customer didn't make changes to the cart
     * after being redirected to the Colissimo gateway.
     *
     * @throws LocalizedException
     */
    protected function validateQuote()
    {
        $quoteHash = $this->dataHelper->getQuoteHash($this->checkoutSession->getQuote());

        if (!hash_equals($quoteHash, $this->checkoutSession->getData('colissimosimplicite_quote_hash'))) {
            $this->throwError(__('The contents of your cart have changed. Please select your shipping method again.'));
        }
    }

    /**
     * Check whether the observer threw an exception.
     *
     * @param Phrase $message
     * @throws LocalizedException
     */
    protected function throwError(Phrase $message)
    {
        $this->errorOccurred = true;
        $this->errorMessage = $message;
        throw new LocalizedException($message);
    }

    /**
     * Check whether the observer threw an exception.
     *
     * @return bool
     */
    public function hasErrorOccurred()
    {
        return $this->errorOccurred;
    }

    /**
     * Get the exception message that was thrown.
     *
     * @return Phrase
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
