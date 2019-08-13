<?php

/**
 * Magento CardGate payment extension
 *
 * @category Mage
 * @package Cardgate_Cgp
 */
class Cardgate_Cgp_Model_Adminhtml_System_Config_Source_Version
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

	public function render ()
	{
        return ( string ) Mage::getConfig()->getNode( 'modules/Cardgate_Cgp/version' );
	}
	
}