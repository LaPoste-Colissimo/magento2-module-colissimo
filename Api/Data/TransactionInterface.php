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
namespace LaPoste\ColissimoSimplicite\Api\Data;

/**
 * Data API for transaction model.
 *
 * @author Smile (http://www.smile.fr)
 */
interface TransactionInterface
{
    /**
     * Transaction ID field.
     */
    const TRANSACTION_ID = 'transaction_id';

    /**
     * Quote ID field.
     */
    const QUOTE_ID = 'quote_id';

    /**
     * Signature field.
     */
    const SIGNATURE = 'signature';

    /**
     * Creation time field.
     */
    const CREATED_AT = 'created_at';

    /**
     * Transaction data field (not saved in databasse).
     */
    const TRANSACTION_DATA = 'transaction_data';

    /**
     * Get the transaction ID.
     *
     * @return int
     */
    public function getTransactionId();

    /**
     * Set the transaction ID.
     *
     * @param int $value
     * @return $this
     */
    public function setTransactionId($value);

    /**
     * Get the quote ID.
     *
     * @return int
     */
    public function getQuoteId();

    /**
     * Set the quote ID.
     *
     * @param int $value
     * @return $this
     */
    public function setQuoteId($value);

    /**
     * Get the signature.
     *
     * @return string
     */
    public function getSignature();

    /**
     * Set the signature.
     *
     * @param string $value
     * @return $this
     */
    public function setSignature($value);

    /**
     * Get the creation time.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set the creation time.
     *
     * @param string $value
     * @return $this
     */
    public function setCreatedAt($value);

    /**
     * Get the transaction data.
     *
     * @return array
     */
    public function getTransactionData();

    /**
     * Set the transaction data.
     *
     * @param array $data
     * @return $this
     */
    public function setTransactionData(array $data);
}
