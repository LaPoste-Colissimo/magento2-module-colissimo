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
namespace LaPoste\ColissimoSimplicite\Block;

use LaPoste\ColissimoSimplicite\Api\Data\TransactionInterface;
use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use LaPoste\ColissimoSimplicite\Helper\Data as DataHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote\Address;

/**
 * Colissimo form block.
 * The form is automatically submitted.
 * The customer is redirected to the Colissimo FO.
 *
 * @author Smile (http://www.smile.fr)
 */
class Form extends Template
{
    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * @var string
     */
    protected $formActionUrl;

    /**
     * @param Context $context
     * @param DataHelper $dataHelper
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Context $context,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Get the transaction object.
     *
     * @return TransactionInterface
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Set the transaction object.
     *
     * @param TransactionInterface $transaction
     * @return $this
     */
    public function setTransaction(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get the form action URL.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->formActionUrl;
    }

    /**
     * Set the form action URL.
     *
     * @param string $url
     * @return $this
     */
    public function setFormActionUrl($url)
    {
        $this->formActionUrl = $url;

        return $this;
    }

    /**
     * Get the message displayed during the redirection.
     *
     * @return string
     */
    public function getRedirectMessage()
    {
        return $this->configHelper->getRedirectMessage();
    }
}
