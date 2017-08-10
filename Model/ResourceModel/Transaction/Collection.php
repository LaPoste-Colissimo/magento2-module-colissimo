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
namespace LaPoste\ColissimoSimplicite\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Transaction resource collection.
 *
 * @author Smile (http://www.smile.fr)
 */
class Collection extends AbstractCollection
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(
            'LaPoste\ColissimoSimplicite\Model\Transaction',
            'LaPoste\ColissimoSimplicite\Model\ResourceModel\Transaction'
        );
    }
}
