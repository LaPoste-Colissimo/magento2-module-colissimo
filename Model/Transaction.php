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
namespace LaPoste\ColissimoSimplicite\Model;

use LaPoste\ColissimoSimplicite\Api\Data\TransactionInterface;
use LaPoste\ColissimoSimplicite\Helper\Config as ConfigHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Transaction model.
 *
 * @author Smile (http://www.smile.fr)
 */
class Transaction extends AbstractModel implements TransactionInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ConfigHelper $configHelper
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ConfigHelper $configHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->configHelper = $configHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('LaPoste\ColissimoSimplicite\Model\ResourceModel\Transaction');
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setTransactionId($value)
    {
        return $this->setData(self::TRANSACTION_ID, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setQuoteId($value)
    {
        return $this->setData(self::QUOTE_ID, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature()
    {
        return $this->getData(self::SIGNATURE);
    }

    /**
     * {@inheritdoc}
     */
    public function setSignature($value)
    {
        return $this->setData(self::SIGNATURE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($value)
    {
        return $this->setData(self::CREATED_AT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionData()
    {
        return $this->getData(self::TRANSACTION_DATA);
    }

    /**
     * {@inheritdoc}
     */
    public function setTransactionData(array $data)
    {
        $this->setData(self::TRANSACTION_DATA, $data);

        // Generate the signature from the data
        $data[] = $this->configHelper->getEncryptionKey();
        $this->setSignature($this->generateSignature($data));

        return $this;
    }

    /**
     * Generate a signature from a data array.
     *
     * @param array $transactionData
     * @return string
     */
    protected function generateSignature(array $transactionData)
    {
        $signature = '';

        foreach ($transactionData as $value) {
            // Skip empty fields, it is not mandatory but it reduces the data to send
            if ($value !== null && $value !== '' && $value !== false) {
                $signature .= utf8_decode($value);
            }
        }

        return sha1($signature);
    }
}
