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
namespace LaPoste\ColissimoSimplicite\Controller\Form;

use LaPoste\ColissimoSimplicite\Api\Data\TransactionInterface;
use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use LaPoste\ColissimoSimplicite\Model\TransactionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Model\Quote\Address;

/**
 * Abstract controller for the form that redirects to the Colissimo gateway.
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
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var Resolver
     */
    protected $localeResolver;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param DataHelper $dataHelper
     * @param ConfigHelper $configHelper
     * @param TransactionFactory $transactionFactory
     * @param Resolver $localeResolver
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        TransactionFactory $transactionFactory,
        Resolver $localeResolver
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->transactionFactory = $transactionFactory;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Get the URL of the form (URL to the Colissimo gateway).
     *
     * @return string
     */
    abstract public function getFormActionUrl();

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // Clear Colissimo data stored in session
        $this->dataHelper->clearCheckoutSession(false);

        // Get current shipping address
        $shippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();

        // Recalculate shipping rates to make sure the transaction will be created using the correct amounts
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();

        // Store a hash of the quote in session
        // (used in an observer to prevent submitting orders if the cart was modified during the payment step)
        $this->checkoutSession->setData(
            'colissimosimplicite_quote_hash',
            $this->dataHelper->getQuoteHash($this->checkoutSession->getQuote())
        );

        // Create the transaction
        $transaction = $this->transactionFactory->create();
        $transaction->setQuoteId($shippingAddress->getQuoteId());
        $transaction->save();

        // Set the transaction data and calculate the signature
        // (a new save is required because the signature includes the transaction ID)
        $transactionData = $this->getTransactionData($transaction, $shippingAddress);
        $transaction->setTransactionData($transactionData);
        $transaction->save();

        // Relay the transaction to the form block
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $resultLayout->getLayout()
            ->getBlock('colissimosimplicite.form')
            ->setFormActionUrl($this->getFormActionUrl())
            ->setTransaction($transaction);

        return $resultLayout;
    }

    /**
     * Generate a signature from the data stored in the quote address.
     *
     * @param TransactionInterface $transaction
     * @param Address $address
     */
    protected function getTransactionData(TransactionInterface $transaction, Address $address)
    {
        // Get the shipping amounts
        $amounts = $this->checkoutSession->getData('colissimosimplicite_available_shipping_amounts');
        $defaultShippingAmount = $this->dataHelper
            ->getShippingAmountForColissimo($amounts['default'], $address);
        $pickupShippingAmount = $this->dataHelper
            ->getShippingAmountForColissimo($amounts['pickup'], $address);

        return [
            'pudoFOId' => $this->configHelper->getAccountId(),
            'ceName' => $address->getLastname(),
            'dyForwardingCharges' => $defaultShippingAmount,
            'dyForwardingChargesCMT' => $pickupShippingAmount,
            'trClientNumber' => $address->getCustomerId(),
            'trOrderNumber' => $address->getQuoteId(),
            'orderId' => (string) ($transaction->getTransactionId() + 9999), // at least 5 digits
            'numVersion' => $this->configHelper->getNumVersion(),
            'ceCivility' => $this->dataHelper->getPrefixForColissimo($address->getPrefix()),
            'ceFirstName' => $address->getFirstname(),
            'ceCompanyName' => $address->getCompany(),
            'ceAdress1' => $this->dataHelper->getStreetLine($address, 2),
            'ceAdress2' => $this->dataHelper->getStreetLine($address, 3),
            'ceAdress3' => $this->dataHelper->getStreetLine($address, 1),
            'ceAdress4' => $this->dataHelper->getStreetLine($address, 4),
            'ceZipCode' => $address->getPostcode(),
            'ceTown' => $address->getCity(),
            'ceEmail' => $address->getEmail(),
            'cePhoneNumber' => $this->dataHelper->getFormattedPhoneNumber($address),
            'dyWeight' => $this->dataHelper->getFormattedWeight($address),
            'trReturnUrlKo' => $this->configHelper->getFailureUrl(),
            'trReturnUrlOk' => $this->configHelper->getSuccessUrl(),
            'cePays' => $address->getCountryId(),
            'trInter' => $address->getCountryId() === 'FR' ? '0' : '2',
            'ceLang' => $this->localeResolver->getLocale(),
        ];
    }
}
