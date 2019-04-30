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
namespace LaPoste\ColissimoSimplicite\Controller\Result;

use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use LaPoste\ColissimoSimplicite\Model\Carrier;
use LaPoste\ColissimoSimplicite\Model\TransactionFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Success controller.
 *
 * @author Smile (http://www.smile.fr)
 */
class Success extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

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
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param DataHelper $dataHelper
     * @param ConfigHelper $configHelper
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Session $checkoutSession,
        DataHelper $dataHelper,
        ConfigHelper $configHelper,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @var array
     */
    protected $paramsNotSavedInSession = [
        'NUMVERSION',
        'ORDERID',
        'PUDOFOID',
        'SIGNATURE',
        'TRCLIENTNUMBER',
        'TRORDERNUMBER',
        'TRPARAMPLUS',
        'TRRETURNURLKO',
    ];

    /**
     * @var array
     */
    protected $signatureFields = [
        'PUDOFOID',
        'CENAME',
        'DYPREPARATIONTIME',
        'DYFORWARDINGCHARGES',
        'TRCLIENTNUMBER',
        'TRORDERNUMBER',
        'ORDERID',
        'CECIVILITY',
        'CEFIRSTNAME',
        'CECOMPANYNAME',
        'CEADRESS1',
        'CEADRESS2',
        'CEADRESS3',
        'CEADRESS4',
        'CEZIPCODE',
        'CETOWN',
        'DELIVERYMODE',
        'PRID',
        'PRNAME',
        'PRCOMPLADRESS',
        'PRADRESS1',
        'PRADRESS2',
        'PRZIPCODE',
        'PRTOWN',
        'LOTACHEMINEMENT',
        'DISTRIBUTIONSORT',
        'VERSIONPLANTRI',
        'CEDELIVERYINFORMATION',
        'CEEMAIL',
        'CEPHONENUMBER',
        'CEDOORCODE1',
        'CEDOORCODE2',
        'CEENTRYPHONE',
        'TRPARAMPLUS',
        'TRADERCOMPANYNAME',
        'ERRORCODE',
        'ERR_CENAME',
        'ERR_CEFIRSTNAME',
        'ERR_CECOMPANYNAME',
        'ERR_CEADRESS1',
        'ERR_CEADRESS2',
        'ERR_CEADRESS3',
        'ERR_CEADRESS4',
        'ERR_CETOWN',
        'ERR_CEDOORCODE1',
        'ERR_CEDOORCODE2',
        'ERR_CEENTRYPHONE',
        'ERR_CEDELIVERYINFORMATION',
        'ERR_CEEMAIL',
        'ERR_CEPHONENUMBER',
        'ERR_TRCLIENTNUMBER',
        'ERR_TRORDERNUMBER',
        'ERR_TRPARAMPLUS',
        'ERR_CECIVILITY',
        'ERR_DYWEIGHT',
        'ERR_DYPREPARATIONTIME',
        'TRRETURNURLKO',
        'CHARSET',
        'CEPAYS',
        'PRPAYS',
        'CODERESEAU',
        'ERR_CHARSET',
    ];

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            // Get the data returned by the Colissimo gateway
            $colissimoData = $this->getRequest()->getParams();

            // Force it to uppercase because data coming from the mobile gateway is in camelCase
            // whereas data coming from the web gateway (iframe) is in uppercase
            $colissimoData = array_change_key_case($colissimoData, CASE_UPPER);

            // Validate the signature of the transaction
            $this->validateSignature($colissimoData);

            // Update the shipping address with the shipping data returned by the Colissimo gateway
            $this->updateShippingAddress($colissimoData);

            // Save the data in the session (only keep useful data), it will then be stored in the order
            $colissimoData = array_diff_key($colissimoData, array_flip($this->paramsNotSavedInSession));
            $this->checkoutSession->setData('colissimosimplicite_shipping_data', $colissimoData);

            $this->_redirect('checkout', ['_fragment' => 'payment']);
        } catch (LocalizedException $e) {
            $this->dataHelper->clearCheckoutSession();
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_redirect($this->configHelper->getRedirectUrlOnError());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->dataHelper->clearCheckoutSession();
            $this->messageManager->addErrorMessage(__('An unexpected error occurred. Please try again later.'));
            $this->_redirect($this->configHelper->getRedirectUrlOnError());
        }
    }

    /**
     * Validate the signature of the transaction.
     *
     * @param array $colissimoData
     * @throws LocalizedException
     */
    protected function validateSignature(array $colissimoData)
    {
        $transactionId = $colissimoData['ORDERID'];
        $signature = $colissimoData['SIGNATURE'];

        // Build the data array used to generate the signature
        $transactionData = [];
        foreach ($this->signatureFields as $field) {
            if (isset($colissimoData[$field])) {
                $transactionData[$field] = $colissimoData[$field];
            }
        }

        // Create the transaction object and generate the signature
        $transaction = $this->transactionFactory->create()
            ->load($transactionId - 9999)
            ->setTransactionData($transactionData);

        // Make sure the transaction exists and belongs to the current quote
        if (!$transaction->getId() || $transaction->getQuoteId() != $this->checkoutSession->getQuoteId()) {
            throw new LocalizedException(__('Colissimo: the transaction ID "%1" is not valid.', $transactionId));
        }

        // Validate the signature by calculating it on our side and comparing it by the signature in the POST data
        if (!$signature || $signature !== $transaction->getSignature()) {
            throw new LocalizedException(__('Colissimo: the signature is not valid.'));
        }
    }

    /**
     * Update the shipping address with the data returned from Colissimo.
     *
     * @param array $colissimoData
     */
    protected function updateShippingAddress(array $colissimoData)
    {
        $quote = $this->checkoutSession->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        $deliveryMode = $colissimoData['DELIVERYMODE'];
        $shippingAmount = $colissimoData['DYFORWARDINGCHARGES'];

        // Get the shipping address data returned by the Colissimo gateway
        $shippingAddressData = $this->getShippingAddressDataFromColissimoData($colissimoData);

        // Save data in session
        $shippingAmount = $this->dataHelper->getShippingAmountForMagento($shippingAmount, $shippingAddress);
        $this->checkoutSession->setData('colissimosimplicite_chosen_delivery_mode', $deliveryMode);
        $this->checkoutSession->setData('colissimosimplicite_chosen_shipping_amount', $shippingAmount);
        $this->checkoutSession->setData('colissimosimplicite_status', Carrier::STATUS_COMPLETE);

        // Save the shipping address and calculate the shipping amount again
        // (because it may have been modified on the Colissimo gateway)
        $shippingAddress->addData($shippingAddressData);
        $shippingAddress->setCollectShippingRates(true);
        $quote->collectTotals()->save();
    }

    /**
     * Parse Colissimo data to get shipping address data.
     *
     * @param array $colissimoData
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getShippingAddressDataFromColissimoData($colissimoData)
    {
        $shippingAddressData = [
            'customer_address_id' => null,
            'street' => [],
            'customer_notes' => [],
            'same_as_billing' => true,
        ];

        foreach ($colissimoData as $field => $value) {
            if ($value === '') {
                continue;
            }

            switch ($field) {
                // Name
                case 'CECIVILITY':
                    $shippingAddressData['prefix'] = $this->dataHelper->getPrefixForMagento($value);
                    break;
                case 'CENAME':
                    $shippingAddressData['lastname'] = $value;
                    break;
                case 'CEFIRSTNAME':
                    $shippingAddressData['firstname'] = $value;
                    break;

                // Address
                case 'CETOWN':
                    $shippingAddressData['city'] = $value;
                    break;
                case 'CEZIPCODE':
                    $shippingAddressData['postcode'] = $value;
                    break;
                case 'CEEMAIL':
                    $shippingAddressData['email'] = $value;
                    break;
                case 'CEPAYS':
                    $shippingAddressData['country_id'] = $value;
                    break;
                case 'CEPHONENUMBER':
                    $shippingAddressData['telephone'] = $value;
                    break;
                case 'CECOMPANYNAME':
                    $shippingAddressData['company'] = $value;
                    break;
                case 'PRZIPCODE':
                    $shippingAddressData['postcode'] = $value;
                    break;
                case 'PRTOWN':
                    $shippingAddressData['city'] = $value;
                    break;

                // Home delivery
                case 'CEADRESS3':
                    // Put it first because this one is mandatory
                    $shippingAddressData['street']['0'] = $value;
                    break;
                case 'CEADRESS1':
                    $shippingAddressData['street']['1'] = $value;
                    break;
                case 'CEADRESS2':
                    $shippingAddressData['street']['2'] = $value;
                    break;
                case 'CEADRESS4':
                    $shippingAddressData['street']['3'] = $value;
                    break;

                // Relay point delivery
                case 'PRNAME':
                    $shippingAddressData['same_as_billing'] = false;
                    $shippingAddressData['street']['0'] = $value;
                    break;
                case 'PRCOMPLADRESS':
                    $shippingAddressData['street']['1'] = $value;
                    break;
                case 'PRADRESS1':
                    $shippingAddressData['street']['2'] = $value;
                    break;
                case 'PRADRESS2':
                    $shippingAddressData['street']['3'] = $value;
                    break;

                // Other shipping data
                case 'CEENTRYPHONE':
                    $shippingAddressData['customer_notes']['0'] = __('Intercom: %1', $value);
                    break;
                case 'CEDOORCODE1':
                    $shippingAddressData['customer_notes']['1'] = __('Door code: %1', $value);
                    break;
                case 'CEDOORCODE2':
                    $shippingAddressData['customer_notes']['2'] = __('Door code 2: %1', $value);
                    break;
                case 'CEDELIVERYINFORMATION':
                    $shippingAddressData['customer_notes']['3'] = $value;
                    break;
            }
        }

        $shippingAddressData['customer_notes'] = implode("\n", $shippingAddressData['customer_notes']);

        // Save shipping address in address book only if the chosen mode is home delivery
        if ($colissimoData['DELIVERYMODE'] !== DataHelper::DELIVERY_MODE_HOME) {
            $shippingAddressData['save_in_address_book'] = 0;
        }

        return $shippingAddressData;
    }
}
