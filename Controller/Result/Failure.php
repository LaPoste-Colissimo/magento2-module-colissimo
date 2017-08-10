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

use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Failure controller.
 *
 * @author Smile (http://www.smile.fr)
 */
class Failure extends Action
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->configHelper = $configHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // Get error code
        $errCode = trim($this->getRequest()->getParam('errorCode'));
        $errCodeToLabel = [
            '001' => __('FO identifier is missing'),
            '002' => __('FO identifier is wrong'),
            '003' => __('User is not allowed'),
            '004' => __('Mandatory field(s) are missing'),
            '006' => __('Signature is missing'),
            '007' => __('Signature is not valid'),
            '008' => __('Postcode is not valid'),
            '009' => __('Return URL format is not valid'),
            '010' => __('Failure URL format is not valid'),
            '011' => __('Transaction ID not valid'),
            '012' => __('Shipping costs format is not valid'),
            '015' => __('Server not available'),
            '016' => __('DBMS not available'),
            '020' => __('Incompatible country'),
        ];

        // Add the error message to the session and redirect to the cart
        $message = array_key_exists($errCode, $errCodeToLabel)
            ? __('Colissimo: %1', $errCodeToLabel[$errCode])
            : __('Unidentified error');

        $this->messageManager->addErrorMessage($message);
        $this->_redirect($this->configHelper->getRedirectUrlOnError());
    }
}
