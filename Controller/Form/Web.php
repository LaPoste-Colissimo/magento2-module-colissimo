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
namespace LaPoste\ColissimoSimplicite\Controller\Form;

/**
 * Controller for the form that redirects to the web gateway.
 *
 * @author Smile (http://www.smile.fr)
 */
class Web extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function getFormActionUrl()
    {
        $returnUrlKo = $this->configHelper->getFailureUrl();

        return $this->configHelper->getWebGatewayUrl() . '?trReturnUrlKo=' . $returnUrlKo;
    }
}
