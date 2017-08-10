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
namespace LaPoste\ColissimoSimplicite\Model\ResourceModel;

use LaPoste\ColissimoSimplicite\Api\Data\TransactionInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Transaction resource model.
 *
 * @author Smile (http://www.smile.fr)
 */
class Transaction extends AbstractDb
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('colissimosimplicite_transaction', 'transaction_id');
    }
}
