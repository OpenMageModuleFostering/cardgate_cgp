<?php
/**
 * Magento CardGatePlus payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Mage
 * @package     Cardgate_Cgp
 * @author      Paul Saparov, <support@cardgate.com>
 * @copyright   Copyright (c) 2011 CardGatePlus B.V. (http://www.cardgateplus.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Cardgate_Cgp_Block_Redirect extends Mage_Core_Block_Template
{    
    protected function _construct()
    {
    		$this->setTemplate('cardgate/cgp/redirect.phtml');
    }
    
    public function getForm()
    {
        $model = Mage::getModel(Mage::registry('cgp_model'));
        Mage::unregister('cgp_model');
        
        $form = new Varien_Data_Form();
        $form->setAction($model->getGatewayUrl())
             ->setId('cardgateplus_checkout')
             ->setName('cardgateplus_checkout')
             ->setMethod('POST')
             ->setUseContainer(true);
        
        foreach ($model->getCheckoutFormFields() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }
        return $form->getHtml();
    }
}
