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
namespace LaPoste\ColissimoSimplicite\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Source for amount calculation type.
 *
 * @author Smile (http://www.smile.fr)
 */
class AmountBaseType implements ArrayInterface
{
    /**
     * Fixed amount calculation.
     */
    const FIXED = 'fixed';

    /**
     * Calculate shipping amount depending on the total weight of the cart.
     */
    const PER_WEIGHT = 'per_weight';


    /**
     * Calculate shipping amount depending on the total amount of the cart.
     */
    const PER_AMOUNT = 'per_amount';

    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                [
                    'value' => self::FIXED,
                    'label' => __('Fixed Price'),
                ],
                [
                    'value' => self::PER_WEIGHT,
                    'label' => __('Price By Weight'),
                ],
                [
                    'value' => self::PER_AMOUNT,
                    'label' => __('Price By Cart Subtotal'),
                ],
            ];
        }

        return $this->options;
    }
}
