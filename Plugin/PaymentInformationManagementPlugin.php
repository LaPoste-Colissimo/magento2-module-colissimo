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

use LaPoste\ColissimoSimplicite\Observer\ValidateOnSubmit;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Plugin for the payment information management model.
 *
 * @author Smile (http://www.smile.fr)
 */
class PaymentInformationManagementPlugin
{
    /**
     * @var ValidateOnSubmit
     */
    protected $observer;

    /**
     * @param ValidateOnSubmit $observer
     */
    public function __construct(ValidateOnSubmit $observer)
    {
        $this->observer = $observer;
    }

    /**
     * Display the exception message instead of a generic message when the exception was thrown by Colissimo.
     *
     * @param PaymentInformationManagement $paymentInformationManagement
     * @param callable $proceed
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $paymentInformationManagement,
        $proceed,
        $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ) {
        try {
            $result = $proceed($cartId, $paymentMethod, $billingAddress);
        } catch (CouldNotSaveException $e) {
            if ($this->observer->hasErrorOccurred()) {
                throw new CouldNotSaveException($this->observer->getErrorMessage(), $e);
            } else {
                throw $e;
            }
        }

        return $result;
    }
}
