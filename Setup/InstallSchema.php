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
namespace LaPoste\ColissimoSimplicite\Setup;

use LaPoste\ColissimoSimplicite\Api\Data\TransactionInterface;
use LaPoste\ColissimoSimplicite\Helper\Config;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Install setup.
 *
 * @author Smile (http://www.smile.fr)
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->createTransactionTable($setup);
        $this->alterSalesOrderTable($setup);
        $setup->endSetup();
    }

    /**
     * Create the table that stores the Colissimo transaction data.
     *
     * @param SchemaSetupInterface $setup
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function createTransactionTable($setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('colissimosimplicite_transaction'))
            ->addColumn(
                TransactionInterface::TRANSACTION_ID,
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Transaction Id'
            )
            ->addColumn(
                TransactionInterface::QUOTE_ID,
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Quote Id'
            )
            ->addColumn(
                TransactionInterface::SIGNATURE,
                Table::TYPE_TEXT,
                255,
                [],
                'Signature'
            )
            ->addColumn(
                TransactionInterface::CREATED_AT,
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->addForeignKey(
                $setup->getFkName(
                    'colissimosimplicite_transaction',
                    TransactionInterface::QUOTE_ID,
                    'quote',
                    'entity_id'
                ),
                TransactionInterface::QUOTE_ID,
                $setup->getTable('quote'),
                'entity_id',
                Table::ACTION_CASCADE
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     * Add Colissimo data column to the order table.
     *
     * @param SchemaSetupInterface $setup
     */
    protected function alterSalesOrderTable($setup)
    {
        $setup->getConnection()
            ->addColumn(
                $setup->getTable('sales_order'),
                Config::FIELD_COLISSIMO_DATA,
                [
                    'type' => Table::TYPE_TEXT,
                    'comment' => 'Colissimo Data',
                    'nullable' => true,
                ]
            );
    }
}
