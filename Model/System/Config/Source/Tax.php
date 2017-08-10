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
 * Source for tax display.
 *
 * @author Smile (http://www.smile.fr)
 */
class Tax implements ArrayInterface
{
    /**
     * Display prices excluding tax.
     */
    const EXCLUDING_TAX = 1;

    /**
     * Display prices including tax.
     */
    const INCLUDING_TAX = 2;

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
                    'value' => self::EXCLUDING_TAX,
                    'label' => __('Excluding Tax'),
                ],
                [
                    'value' => self::INCLUDING_TAX,
                    'label' => __('Including Tax'),
                ],
            ];
        }

        return $this->options;
    }
}
