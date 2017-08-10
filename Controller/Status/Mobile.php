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
namespace LaPoste\ColissimoSimplicite\Controller\Status;

/**
 * Controller for mobile gateway status.
 *
 * @author Smile (http://www.smile.fr)
 */
class Mobile extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function isGatewayAvailable()
    {
        return $this->gatewayHelper->isMobileGatewayAvailable();
    }
}
